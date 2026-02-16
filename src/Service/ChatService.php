<?php

namespace App\Service;

use App\Entity\Conversation;
use App\Entity\ConversationParticipant;
use App\Entity\Message;
use App\Entity\MessageAttachment;
use App\Entity\User;
use App\Repository\ConversationParticipantRepository;
use App\Repository\ConversationRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ChatService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ConversationRepository $conversationRepository,
        private readonly ConversationParticipantRepository $participantRepository,
        private readonly HubInterface $hub,
        private readonly HttpClientInterface $httpClient,
        private readonly UrlGeneratorInterface $urlGenerator,
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
        #[Autowire('%chat_upload_dir%')]
        private readonly string $chatUploadDir,
        #[Autowire('%chat_upload_public_prefix%')]
        private readonly string $chatUploadPublicPrefix,
        #[Autowire('%chat_upload_max_size_bytes%')]
        private readonly int $chatUploadMaxSizeBytes,
        #[Autowire('%chat_upload_max_files_per_message%')]
        private readonly int $chatUploadMaxFilesPerMessage,
        #[Autowire('%chat_upload_allowed_mime_types%')]
        private readonly array $chatUploadAllowedMimeTypes,
        #[Autowire('%env(CHAT_WS_BRIDGE_URL)%')]
        private readonly string $chatWebSocketBridgeUrl,
        #[Autowire('%env(CHAT_WS_BRIDGE_SECRET)%')]
        private readonly string $chatWebSocketBridgeSecret,
        #[Autowire('%kernel.secret%')]
        private readonly string $kernelSecret,
        private readonly LoggerInterface $logger
    ) {
    }

    public function createOrGetPrivateConversation(User $userA, User $userB): Conversation
    {
        $userAId = $userA->getId();
        $userBId = $userB->getId();

        if ($userAId === null || $userBId === null || $userAId === $userBId) {
            throw new \InvalidArgumentException('Invalid private conversation members.');
        }

        $privateKey = $this->conversationRepository->buildPrivateKey($userAId, $userBId);
        $existing = $this->conversationRepository->findPrivateBetweenUsers($userA, $userB);

        if ($existing instanceof Conversation) {
            $existing
                ->setIsGroup(false)
                ->setTitle(null)
                ->setPrivateKey($privateKey)
                ->setUpdatedAt(new \DateTimeImmutable());

            $this->ensureParticipant($existing, $userA, ConversationParticipant::ROLE_MEMBER);
            $this->ensureParticipant($existing, $userB, ConversationParticipant::ROLE_MEMBER);
            $this->em->flush();

            return $existing;
        }

        $conversation = (new Conversation())
            ->setIsGroup(false)
            ->setTitle(null)
            ->setPrivateKey($privateKey);

        $this->em->persist($conversation);
        $this->attachParticipant($conversation, $userA, ConversationParticipant::ROLE_MEMBER);
        $this->attachParticipant($conversation, $userB, ConversationParticipant::ROLE_MEMBER);

        try {
            $this->em->flush();
        } catch (UniqueConstraintViolationException) {
            $alreadyCreated = $this->conversationRepository->findPrivateBetweenUsers($userA, $userB);
            if ($alreadyCreated instanceof Conversation) {
                return $alreadyCreated;
            }

            throw new \RuntimeException('Unable to create private conversation.');
        }

        $this->publishConversationUpdate($conversation);

        return $conversation;
    }

    /**
     * @param User[] $selectedParents
     */
    public function createGroupConversation(User $creator, array $selectedParents, string $groupName): Conversation
    {
        $groupName = trim($groupName);
        if ($groupName === '') {
            throw new \InvalidArgumentException('Group name is required.');
        }

        $members = $this->normalizeGroupMembers($creator, $selectedParents);
        if (count($members) < 3) {
            throw new \InvalidArgumentException('A group must include at least 3 members.');
        }

        $conversation = (new Conversation())
            ->setIsGroup(true)
            ->setTitle($groupName)
            ->setPrivateKey(null);

        $this->em->persist($conversation);

        foreach ($members as $member) {
            $role = $member->getId() === $creator->getId()
                ? ConversationParticipant::ROLE_ADMIN
                : ConversationParticipant::ROLE_MEMBER;
            $this->attachParticipant($conversation, $member, $role);
        }

        $this->em->flush();
        $this->publishConversationUpdate($conversation);

        return $conversation;
    }

    public function leaveGroup(Conversation $conversation, User $actor): void
    {
        if (!$conversation->isGroup()) {
            throw new \InvalidArgumentException('Only group conversations can be left.');
        }

        $membership = $this->participantRepository->findActiveForConversationAndUser($conversation, $actor);
        if (!$membership instanceof ConversationParticipant) {
            throw new \RuntimeException('You are not a member of this conversation.');
        }

        $wasAdmin = $membership->isAdmin();
        $membership->setDeletedAt(new \DateTimeImmutable());
        $conversation->setUpdatedAt(new \DateTimeImmutable());

        if ($wasAdmin) {
            $this->ensureAtLeastOneGroupAdmin($conversation);
        }

        $this->em->flush();
        $this->publishConversationUpdate($conversation);
    }

    public function addMemberToGroup(Conversation $conversation, User $member): void
    {
        if (!$conversation->isGroup()) {
            throw new \InvalidArgumentException('Members can be added only to groups.');
        }

        $existing = $this->participantRepository->findForConversationAndUser($conversation, $member);
        if ($existing instanceof ConversationParticipant) {
            if ($existing->getDeletedAt() === null) {
                throw new \InvalidArgumentException('User is already a member of this group.');
            }

            $existing
                ->setDeletedAt(null)
                ->setRole(ConversationParticipant::ROLE_MEMBER);
        } else {
            $this->attachParticipant($conversation, $member, ConversationParticipant::ROLE_MEMBER);
        }

        $conversation->setUpdatedAt(new \DateTimeImmutable());
        $this->em->flush();
        $this->publishConversationUpdate($conversation);
    }

    public function removeMemberFromGroup(Conversation $conversation, User $member): void
    {
        if (!$conversation->isGroup()) {
            throw new \InvalidArgumentException('Members can be removed only from groups.');
        }

        $membership = $this->participantRepository->findActiveForConversationAndUser($conversation, $member);
        if (!$membership instanceof ConversationParticipant) {
            throw new \InvalidArgumentException('User is not an active group member.');
        }

        $wasAdmin = $membership->isAdmin();
        $membership->setDeletedAt(new \DateTimeImmutable());
        $conversation->setUpdatedAt(new \DateTimeImmutable());

        if ($wasAdmin) {
            $this->ensureAtLeastOneGroupAdmin($conversation);
        }

        $this->em->flush();
        $this->publishConversationUpdate($conversation);
    }

    public function createMessage(
        Conversation $conversation,
        User $sender,
        string $content,
        array $files = []
    ): Message {
        $content = trim($content);
        $normalizedFiles = $this->normalizeUploadedFiles($files);
        if ($content === '' && $normalizedFiles === []) {
            throw new \InvalidArgumentException('Empty message.');
        }

        if (count($normalizedFiles) > $this->chatUploadMaxFilesPerMessage) {
            throw new \InvalidArgumentException(sprintf(
                'Too many attachments. Maximum allowed: %d.',
                $this->chatUploadMaxFilesPerMessage
            ));
        }

        $message = new Message();
        $message
            ->setConversation($conversation)
            ->setSender($sender)
            ->setContent($content)
            ->setStatus('sent')
            ->setType($normalizedFiles === [] ? 'text' : 'file')
            ->setFilePath(null);

        foreach ($normalizedFiles as $file) {
            $storedAttachment = $this->storeUploadedFile($file);
            $attachment = (new MessageAttachment())
                ->setOriginalName($storedAttachment['originalName'])
                ->setStoredName($storedAttachment['storedName'])
                ->setStoragePath($storedAttachment['storagePath'])
                ->setMimeType($storedAttachment['mimeType'])
                ->setSize($storedAttachment['size'])
                ->setIsImage($storedAttachment['isImage']);

            $message->addAttachment($attachment);
        }

        if ($normalizedFiles !== [] && $content === '' && $this->messageHasOnlyImages($message)) {
            $message->setType('image');
        }

        $conversation->setUpdatedAt(new \DateTimeImmutable());
        $this->em->persist($message);
        $this->em->flush();

        $this->publishMessageEvent('message.created', $conversation, $message);
        $this->publishConversationUpdate($conversation);

        return $message;
    }

    public function updateMessage(Message $message, User $editor, string $content): void
    {
        if ($message->getSender()?->getId() !== $editor->getId()) {
            throw new \RuntimeException('Not allowed.');
        }

        $message->setContent($content);
        $message->setUpdatedAt(new \DateTimeImmutable());
        $message->getConversation()?->setUpdatedAt(new \DateTimeImmutable());
        $this->em->flush();

        $conversation = $message->getConversation();
        if ($conversation instanceof Conversation) {
            $this->publishMessageEvent('message.updated', $conversation, $message);
            $this->publishConversationUpdate($conversation);
        }
    }

    public function deleteMessage(Message $message, User $actor): void
    {
        if ($message->getSender()?->getId() !== $actor->getId()) {
            throw new \RuntimeException('Not allowed.');
        }

        $message->setDeletedAt(new \DateTimeImmutable());
        $message->getConversation()?->setUpdatedAt(new \DateTimeImmutable());
        $this->em->flush();

        $conversation = $message->getConversation();
        if ($conversation instanceof Conversation) {
            $this->publishMessageEvent('message.deleted', $conversation, $message);
            $this->publishConversationUpdate($conversation);
        }
    }

    public function serializeMessage(Message $message): array
    {
        $isDeleted = $message->getDeletedAt() !== null;
        $attachments = $isDeleted ? [] : $this->serializeAttachments($message);
        $serializedType = $isDeleted ? 'text' : $this->resolveSerializedMessageType($message, $attachments);
        $fallbackFilePath = $attachments[0]['url'] ?? ($isDeleted ? null : $message->getFilePath());

        return [
            'id' => $message->getId(),
            'content' => $isDeleted ? 'Message supprime' : $message->getContent(),
            'type' => $serializedType,
            'status' => $message->getStatus(),
            'filePath' => $fallbackFilePath,
            'attachments' => $attachments,
            'senderId' => $message->getSender()?->getId(),
            'senderName' => $this->buildDisplayName($message->getSender()),
            'createdAt' => $message->getCreatedAt()?->format(DATE_ATOM),
            'updatedAt' => $message->getUpdatedAt()?->format(DATE_ATOM),
            'deletedAt' => $message->getDeletedAt()?->format(DATE_ATOM),
        ];
    }

    public function getConversationTopic(Conversation $conversation): string
    {
        return 'chat/conversation/' . $conversation->getId();
    }

    public function getUserTopic(User $user): string
    {
        return 'chat/user/' . $user->getId();
    }

    private function ensureAtLeastOneGroupAdmin(Conversation $conversation): void
    {
        $members = $this->participantRepository->findActiveMembers($conversation);
        $hasAdmin = false;

        foreach ($members as $member) {
            if ($member->isAdmin()) {
                $hasAdmin = true;
                break;
            }
        }

        if ($hasAdmin || $members === []) {
            return;
        }

        $members[0]->setRole(ConversationParticipant::ROLE_ADMIN);
    }

    private function attachParticipant(Conversation $conversation, User $user, string $role): ConversationParticipant
    {
        $participant = (new ConversationParticipant())
            ->setConversation($conversation)
            ->setUser($user)
            ->setRole($role);

        $conversation->addParticipant($participant);
        $this->em->persist($participant);

        return $participant;
    }

    private function ensureParticipant(Conversation $conversation, User $user, string $role): void
    {
        $existing = $this->participantRepository->findForConversationAndUser($conversation, $user);
        if ($existing instanceof ConversationParticipant) {
            $existing
                ->setDeletedAt(null)
                ->setRole($role);

            return;
        }

        $this->attachParticipant($conversation, $user, $role);
    }

    /**
     * @param User[] $selectedParents
     * @return User[]
     */
    private function normalizeGroupMembers(User $creator, array $selectedParents): array
    {
        $members = [$creator];
        $seen = [];
        if ($creator->getId() !== null) {
            $seen[$creator->getId()] = true;
        }

        foreach ($selectedParents as $parent) {
            $parentId = $parent->getId();
            if ($parentId === null || isset($seen[$parentId])) {
                continue;
            }

            $seen[$parentId] = true;
            $members[] = $parent;
        }

        return $members;
    }

    private function buildDisplayName(?User $user): string
    {
        if ($user === null) {
            return 'Utilisateur';
        }

        $parts = array_filter([$user->getFirstName(), $user->getLastName()]);
        if ($parts !== []) {
            return implode(' ', $parts);
        }

        return (string) $user->getEmail();
    }

    public function resolveAttachmentAbsolutePath(MessageAttachment $attachment): string
    {
        $storagePath = trim($attachment->getStoragePath());
        if ($storagePath !== '') {
            if (str_starts_with($storagePath, '/')) {
                return $this->projectDir . '/public' . $storagePath;
            }

            return $storagePath;
        }

        return rtrim($this->chatUploadDir, '/\\') . DIRECTORY_SEPARATOR . $attachment->getStoredName();
    }

    /**
     * @return UploadedFile[]
     */
    private function normalizeUploadedFiles(array $files): array
    {
        $normalized = [];
        foreach ($files as $file) {
            if ($file instanceof UploadedFile) {
                $normalized[] = $file;
            }
        }

        return $normalized;
    }

    private function messageHasOnlyImages(Message $message): bool
    {
        $attachments = $message->getAttachments();
        if ($attachments->isEmpty()) {
            return false;
        }

        foreach ($attachments as $attachment) {
            if (!$attachment->isImage()) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function serializeAttachments(Message $message): array
    {
        $items = [];

        foreach ($message->getAttachments() as $attachment) {
            $attachmentId = $attachment->getId();
            if ($attachmentId === null) {
                continue;
            }

            $url = $this->urlGenerator->generate('chat_attachment_download', ['attachmentId' => $attachmentId]);
            $items[] = [
                'id' => $attachmentId,
                'name' => $attachment->getOriginalName(),
                'mimeType' => $attachment->getMimeType(),
                'size' => $attachment->getSize(),
                'isImage' => $attachment->isImage(),
                'url' => $url,
                'downloadUrl' => $url . '?download=1',
            ];
        }

        if ($items !== []) {
            return $items;
        }

        // Backward compatibility for legacy single-file messages.
        $legacyPath = $message->getFilePath();
        if ($legacyPath !== null && $legacyPath !== '') {
            $fileName = basename($legacyPath);
            $isImage = $message->getType() === 'image';

            return [[
                'id' => null,
                'name' => $fileName,
                'mimeType' => $isImage ? 'image/*' : 'application/octet-stream',
                'size' => 0,
                'isImage' => $isImage,
                'url' => $legacyPath,
                'downloadUrl' => $legacyPath,
            ]];
        }

        return [];
    }

    /**
     * @param array<int, array<string, mixed>> $attachments
     */
    private function resolveSerializedMessageType(Message $message, array $attachments): string
    {
        if ($attachments !== []) {
            $containsOnlyImages = true;
            foreach ($attachments as $attachment) {
                if (!($attachment['isImage'] ?? false)) {
                    $containsOnlyImages = false;
                    break;
                }
            }

            if ($containsOnlyImages && trim((string) $message->getContent()) === '') {
                return 'image';
            }

            return 'file';
        }

        return $message->getType();
    }

    /**
     * @return array{originalName:string,storedName:string,storagePath:string,mimeType:string,size:int,isImage:bool}
     */
    private function storeUploadedFile(UploadedFile $file): array
    {
        if (!$file->isValid()) {
            throw new \InvalidArgumentException('Invalid uploaded file.');
        }

        $size = (int) ($file->getSize() ?? 0);
        if ($size <= 0) {
            throw new \InvalidArgumentException('Uploaded file is empty.');
        }

        if ($size > $this->chatUploadMaxSizeBytes) {
            throw new \InvalidArgumentException(sprintf(
                'Attachment exceeds maximum allowed size (%d MB).',
                (int) ceil($this->chatUploadMaxSizeBytes / 1024 / 1024)
            ));
        }

        $mimeType = (string) $file->getMimeType();
        if ($mimeType === '' || !in_array($mimeType, $this->chatUploadAllowedMimeTypes, true)) {
            throw new \InvalidArgumentException(sprintf('File type "%s" is not allowed.', $mimeType ?: 'unknown'));
        }

        $targetDir = rtrim($this->chatUploadDir, '/\\');
        if (!is_dir($targetDir) && !mkdir($targetDir, 0775, true) && !is_dir($targetDir)) {
            throw new \RuntimeException('Unable to create chat upload directory.');
        }

        $storedName = sprintf('%s.%s', bin2hex(random_bytes(16)), $this->resolveSafeExtension($mimeType));

        try {
            $file->move($targetDir, $storedName);
        } catch (FileException $exception) {
            throw new \RuntimeException('Could not store uploaded file.', 0, $exception);
        }

        $publicPrefix = rtrim($this->chatUploadPublicPrefix, '/');
        $storagePath = $publicPrefix . '/' . $storedName;

        return [
            'originalName' => trim((string) $file->getClientOriginalName()) !== ''
                ? trim((string) $file->getClientOriginalName())
                : $storedName,
            'storedName' => $storedName,
            'storagePath' => $storagePath,
            'mimeType' => $mimeType,
            'size' => $size,
            'isImage' => str_starts_with($mimeType, 'image/'),
        ];
    }

    private function resolveSafeExtension(string $mimeType): string
    {
        return match ($mimeType) {
            'image/jpeg', 'image/jpg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
            'image/bmp' => 'bmp',
            'application/pdf' => 'pdf',
            'application/msword' => 'doc',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
            'application/vnd.ms-excel' => 'xls',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
            'text/plain' => 'txt',
            default => 'bin',
        };
    }

    private function publishMessageEvent(string $type, Conversation $conversation, Message $message): void
    {
        $event = [
            'type' => $type,
            'conversationId' => $conversation->getId(),
            'message' => $this->serializeMessage($message),
        ];
        $payload = json_encode($event, JSON_UNESCAPED_SLASHES);

        if (!is_string($payload)) {
            return;
        }

        $this->safePublish($this->getConversationTopic($conversation), $payload);
        $recipientUserIds = [];

        foreach ($conversation->getParticipants() as $participant) {
            if ($participant->getDeletedAt() !== null) {
                continue;
            }

            $user = $participant->getUser();
            if ($user === null) {
                continue;
            }

            $recipientUserIds[] = (int) $user->getId();
            $this->safePublish($this->getUserTopic($user), $payload);
        }

        $this->safePublishWebSocketBridge($event, $recipientUserIds);
    }

    private function publishConversationUpdate(Conversation $conversation): void
    {
        $event = [
            'type' => 'conversation.updated',
            'conversationId' => $conversation->getId(),
        ];
        $payload = json_encode($event, JSON_UNESCAPED_SLASHES);
        if (!is_string($payload)) {
            return;
        }

        $recipientUserIds = [];
        foreach ($conversation->getParticipants() as $participant) {
            if ($participant->getDeletedAt() !== null) {
                continue;
            }

            $user = $participant->getUser();
            if ($user === null) {
                continue;
            }

            $recipientUserIds[] = (int) $user->getId();
            $this->safePublish($this->getUserTopic($user), $payload);
        }

        $this->safePublishWebSocketBridge($event, $recipientUserIds);
    }

    private function safePublish(string $topic, string $payload): void
    {
        try {
            $this->hub->publish(new Update($topic, $payload));
        } catch (\Throwable $e) {
            $this->logger->warning('Mercure publish failed', ['exception' => $e]);
        }
    }

    /**
     * @param int[] $recipientUserIds
     */
    private function safePublishWebSocketBridge(array $event, array $recipientUserIds): void
    {
        $bridgeUrl = trim($this->chatWebSocketBridgeUrl);
        if ($bridgeUrl === '' || $recipientUserIds === []) {
            return;
        }

        $event['recipientUserIds'] = array_values(array_unique(array_filter(
            array_map(static fn (mixed $id): int => (int) $id, $recipientUserIds),
            static fn (int $id): bool => $id > 0
        )));

        if ($event['recipientUserIds'] === []) {
            return;
        }

        $secret = trim($this->chatWebSocketBridgeSecret) !== ''
            ? trim($this->chatWebSocketBridgeSecret)
            : $this->kernelSecret;

        try {
            $response = $this->httpClient->request('POST', $bridgeUrl, [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'X-Chat-Bridge-Secret' => $secret,
                ],
                'json' => $event,
                'timeout' => 0.35,
            ]);
            $response->getStatusCode();
        } catch (\Throwable $e) {
            $this->logger->warning('WebSocket bridge publish failed', ['exception' => $e]);
        }
    }
}

<?php

namespace App\Service;

use App\Entity\Conversation;
use App\Entity\ConversationParticipant;
use App\Entity\Message;
use App\Entity\User;
use App\Repository\ConversationParticipantRepository;
use App\Repository\ConversationRepository;
use App\Repository\MessageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

class ChatService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ConversationRepository $conversationRepository,
        private readonly ConversationParticipantRepository $participantRepository,
        private readonly MessageRepository $messageRepository,
        private readonly HubInterface $hub,
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
        private readonly LoggerInterface $logger
    ) {
    }

    public function getOrCreateConversation(User $admin, User $parent): Conversation
    {
        $existing = $this->conversationRepository->findBetweenAdminAndParent($admin, $parent);
        if ($existing !== null) {
            $this->ensureParticipants($existing, $admin, $parent);
            $this->em->flush();
            return $existing;
        }

        $conversation = new Conversation();
        $conversation->setAdmin($admin);
        $conversation->setParent($parent);

        $this->attachParticipant($conversation, $admin);
        $this->attachParticipant($conversation, $parent);

        $this->em->persist($conversation);
        $this->em->flush();

        $this->publishConversationUpdate($conversation);

        return $conversation;
    }

    private function ensureParticipants(Conversation $conversation, User $admin, User $parent): void
    {
        $hasAdmin = false;
        $hasParent = false;

        foreach ($conversation->getParticipants() as $participant) {
            $userId = $participant->getUser()?->getId();
            if ($userId === $admin->getId()) {
                $hasAdmin = true;
            }
            if ($userId === $parent->getId()) {
                $hasParent = true;
            }
        }

        if (!$hasAdmin) {
            $this->attachParticipant($conversation, $admin);
        }
        if (!$hasParent) {
            $this->attachParticipant($conversation, $parent);
        }
    }

    public function attachParticipant(Conversation $conversation, User $user): ConversationParticipant
    {
        $participant = new ConversationParticipant();
        $participant->setConversation($conversation);
        $participant->setUser($user);
        $conversation->addParticipant($participant);

        $this->em->persist($participant);

        return $participant;
    }

    public function deleteConversationForUser(Conversation $conversation, User $user): void
    {
        $participant = $this->participantRepository->findForConversationAndUser($conversation, $user);
        if ($participant === null) {
            return;
        }

        $participant->setDeletedAt(new \DateTimeImmutable());
        $this->em->flush();

        $this->publishConversationUpdate($conversation);
    }

    public function createMessage(
        Conversation $conversation,
        User $sender,
        string $content,
        ?UploadedFile $file = null
    ): Message {
        $message = new Message();
        $message->setConversation($conversation);
        $message->setSender($sender);
        $message->setContent($content);

        if ($file !== null) {
            $ext = $file->guessExtension() ?: 'bin';
            $safeName = bin2hex(random_bytes(8)) . '.' . $ext;
            $targetDir = $this->getUploadDir();
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0775, true);
            }
            $file->move($targetDir, $safeName);

            $message->setFilePath('/uploads/chat/' . $safeName);
            $message->setType(str_starts_with((string) $file->getMimeType(), 'image/') ? 'image' : 'file');
        } else {
            $message->setType('text');
        }

        $conversation->setUpdatedAt(new \DateTimeImmutable());
        $this->em->persist($message);

        // Reactiver la conversation pour les participants supprimés
        foreach ($conversation->getParticipants() as $participant) {
            if ($participant->getDeletedAt() !== null && $participant->getUser()?->getId() !== $sender->getId()) {
                $participant->setDeletedAt(null);
            }
        }

        $this->em->flush();

        $this->publishMessageEvent('message.created', $conversation, $message);
        $this->publishConversationUpdate($conversation);

        return $message;
    }

    public function updateMessage(Message $message, User $editor, string $content): void
    {
        if ($message->getSender()?->getId() !== $editor->getId()) {
            throw new \RuntimeException('Not allowed');
        }

        $message->setContent($content);
        $message->setUpdatedAt(new \DateTimeImmutable());
        $message->getConversation()?->setUpdatedAt(new \DateTimeImmutable());
        $this->em->flush();

        $this->publishMessageEvent('message.updated', $message->getConversation(), $message);
        $this->publishConversationUpdate($message->getConversation());
    }

    public function deleteMessage(Message $message, User $actor): void
    {
        if ($message->getSender()?->getId() !== $actor->getId()) {
            throw new \RuntimeException('Not allowed');
        }

        $message->setDeletedAt(new \DateTimeImmutable());
        $message->getConversation()?->setUpdatedAt(new \DateTimeImmutable());
        $this->em->flush();

        $this->publishMessageEvent('message.deleted', $message->getConversation(), $message);
        $this->publishConversationUpdate($message->getConversation());
    }

    private function publishMessageEvent(string $type, Conversation $conversation, Message $message): void
    {
        $payload = json_encode([
            'type' => $type,
            'conversationId' => $conversation->getId(),
            'message' => $this->serializeMessage($message),
        ], JSON_UNESCAPED_SLASHES);

        $this->safePublish($this->getConversationTopic($conversation), $payload);

        foreach ($conversation->getParticipants() as $participant) {
            $user = $participant->getUser();
            if ($user === null) {
                continue;
            }
            $this->safePublish($this->getUserTopic($user), $payload);
        }
    }

    private function publishConversationUpdate(Conversation $conversation): void
    {
        foreach ($conversation->getParticipants() as $participant) {
            $user = $participant->getUser();
            if ($user === null) {
                continue;
            }

            $payload = json_encode([
                'type' => 'conversation.updated',
                'conversationId' => $conversation->getId(),
            ], JSON_UNESCAPED_SLASHES);

            $this->safePublish($this->getUserTopic($user), $payload);
        }
    }

    public function serializeMessage(Message $message): array
    {
        $isDeleted = $message->getDeletedAt() !== null;
        return [
            'id' => $message->getId(),
            'content' => $isDeleted ? 'Message supprimé' : $message->getContent(),
            'type' => $isDeleted ? 'text' : $message->getType(),
            'filePath' => $isDeleted ? null : $message->getFilePath(),
            'senderId' => $message->getSender()?->getId(),
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

    private function getUploadDir(): string
    {
        return $this->projectDir . '/public/uploads/chat';
    }

    private function safePublish(string $topic, string $payload): void
    {
        try {
            $this->hub->publish(new Update($topic, $payload));
        } catch (\Throwable $e) {
            $this->logger->warning('Mercure publish failed', ['exception' => $e]);
        }
    }
}

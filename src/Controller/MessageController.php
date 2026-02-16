<?php

namespace App\Controller;

use App\Entity\Conversation;
use App\Entity\MessageAttachment;
use App\Entity\Message;
use App\Entity\User;
use App\Repository\MessageAttachmentRepository;
use App\Repository\MessageRepository;
use App\Security\Voter\ConversationVoter;
use App\Service\ChatService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/chat')]
class MessageController extends AbstractController
{
    public function __construct(
        private readonly MessageRepository $messageRepository,
        private readonly MessageAttachmentRepository $messageAttachmentRepository,
        private readonly ChatService $chatService
    ) {
    }

    #[Route('/{id}/messages', name: 'chat_messages', methods: ['GET'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_USER')]
    public function listMessages(Conversation $conversation, Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted(ConversationVoter::VIEW, $conversation);

        $page = max(1, (int) $request->query->get('page', 1));
        $perPage = max(1, min((int) $request->query->get('perPage', 30), 100));

        $messages = $this->messageRepository->findPaginatedForConversation($conversation, $page, $perPage);
        $data = array_map(fn (Message $message): array => $this->chatService->serializeMessage($message), $messages);

        return $this->json([
            'items' => $data,
            'page' => $page,
            'perPage' => $perPage,
        ]);
    }

    #[Route('/{id}/messages', name: 'chat_message_create', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[Route('/{id}/message', name: 'chat_message_create_legacy', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_USER')]
    public function sendMessage(Conversation $conversation, Request $request): JsonResponse
    {
        $this->assertCsrf($request);
        $this->denyAccessUnlessGranted(ConversationVoter::MESSAGE, $conversation);
        /** @var User $user */
        $user = $this->getUser();

        $content = trim((string) $request->request->get('content', ''));
        $attachments = $this->extractAttachments($request);

        if ($content === '' && $attachments === []) {
            return $this->json(['error' => 'Empty message.'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $message = $this->chatService->createMessage($conversation, $user, $content, $attachments);
        } catch (\InvalidArgumentException $exception) {
            return $this->json(['error' => $exception->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (\RuntimeException) {
            return $this->json(['error' => 'Unable to upload attachment.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $this->json($this->chatService->serializeMessage($message), Response::HTTP_CREATED);
    }

    #[Route('/messages/{id}', name: 'chat_message_update', methods: ['PUT'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_USER')]
    public function updateMessage(Message $message, Request $request): JsonResponse
    {
        $this->assertCsrf($request);
        $this->denyAccessUnlessGranted(ConversationVoter::MESSAGE, $message->getConversation());
        /** @var User $user */
        $user = $this->getUser();

        $payload = $request->toArray();
        $content = trim((string) ($payload['content'] ?? ''));
        if ($content === '') {
            return $this->json(['error' => 'Empty content.'], Response::HTTP_BAD_REQUEST);
        }

        $this->chatService->updateMessage($message, $user, $content);

        return $this->json($this->chatService->serializeMessage($message));
    }

    #[Route('/messages/{id}', name: 'chat_message_delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_USER')]
    public function deleteMessage(Message $message, Request $request): JsonResponse
    {
        $this->assertCsrf($request);
        $this->denyAccessUnlessGranted(ConversationVoter::MESSAGE, $message->getConversation());
        /** @var User $user */
        $user = $this->getUser();

        $this->chatService->deleteMessage($message, $user);

        return $this->json($this->chatService->serializeMessage($message));
    }

    #[Route('/{id}/images', name: 'chat_conversation_images', methods: ['GET'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_USER')]
    public function recentImages(Conversation $conversation): JsonResponse
    {
        $this->denyAccessUnlessGranted(ConversationVoter::VIEW, $conversation);

        $images = $this->messageRepository->findRecentImages($conversation, 24);
        $data = array_map(fn (Message $m): array => $this->chatService->serializeMessage($m), $images);

        return $this->json($data);
    }

    #[Route('/attachment/{attachmentId}', name: 'chat_attachment_download', methods: ['GET'], requirements: ['attachmentId' => '\d+'])]
    #[IsGranted('ROLE_USER')]
    public function downloadAttachment(int $attachmentId, Request $request): Response
    {
        $attachment = $this->messageAttachmentRepository->find($attachmentId);
        if (!$attachment instanceof MessageAttachment) {
            throw $this->createNotFoundException('Attachment not found.');
        }

        $message = $attachment->getMessage();
        $conversation = $message?->getConversation();
        if (!$message instanceof Message || !$conversation instanceof Conversation) {
            throw $this->createNotFoundException('Attachment message not found.');
        }

        $this->denyAccessUnlessGranted(ConversationVoter::VIEW, $conversation);

        $absolutePath = $this->chatService->resolveAttachmentAbsolutePath($attachment);
        if (!is_file($absolutePath)) {
            throw $this->createNotFoundException('Attachment file missing.');
        }

        $download = $request->query->getBoolean('download');
        $disposition = $download || !$attachment->isImage()
            ? ResponseHeaderBag::DISPOSITION_ATTACHMENT
            : ResponseHeaderBag::DISPOSITION_INLINE;

        $response = new BinaryFileResponse($absolutePath);
        $response->setPrivate();
        $response->headers->set('Content-Type', $attachment->getMimeType());
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->setContentDisposition($disposition, $attachment->getOriginalName());

        return $response;
    }

    /**
     * @return UploadedFile[]
     */
    private function extractAttachments(Request $request): array
    {
        $attachments = [];

        $legacyFile = $request->files->get('file');
        if ($legacyFile instanceof UploadedFile) {
            $attachments[] = $legacyFile;
        }

        $rawAttachments = $request->files->get('attachments');
        if ($rawAttachments instanceof UploadedFile) {
            $attachments[] = $rawAttachments;
        } elseif (is_array($rawAttachments)) {
            foreach ($rawAttachments as $file) {
                if ($file instanceof UploadedFile) {
                    $attachments[] = $file;
                }
            }
        }

        return $attachments;
    }

    private function assertCsrf(Request $request): void
    {
        $token = $request->headers->get('X-CSRF-TOKEN') ?? $request->request->get('_token');
        if (!$this->isCsrfTokenValid('chat_action', (string) $token)) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }
    }
}

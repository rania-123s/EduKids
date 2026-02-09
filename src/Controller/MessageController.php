<?php

namespace App\Controller;

use App\Entity\Conversation;
use App\Entity\Message;
use App\Entity\User;
use App\Repository\MessageRepository;
use App\Service\ChatService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/chat')]
class MessageController extends AbstractController
{
    public function __construct(
        private readonly MessageRepository $messageRepository,
        private readonly ChatService $chatService
    ) {
    }

    #[Route('/conversations/{id}/messages', name: 'chat_messages', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function listMessages(Conversation $conversation, Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $this->assertUserInConversation($conversation, $user);

        $before = $request->query->get('before');
        $beforeDate = $before ? new \DateTimeImmutable($before) : null;
        $messages = $this->messageRepository->findForConversation($conversation, $beforeDate, 100);

        $data = array_map(fn (Message $m) => $this->chatService->serializeMessage($m), $messages);

        return $this->json($data);
    }

    #[Route('/conversations/{id}/messages', name: 'chat_message_create', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function sendMessage(Conversation $conversation, Request $request): JsonResponse
    {
        $this->assertCsrf($request);
        /** @var User $user */
        $user = $this->getUser();
        $this->assertUserInConversation($conversation, $user);

        $content = (string) $request->request->get('content', '');
        /** @var UploadedFile|null $file */
        $file = $request->files->get('file');

        if (trim($content) === '' && $file === null) {
            return $this->json(['error' => 'Empty message'], Response::HTTP_BAD_REQUEST);
        }

        $message = $this->chatService->createMessage($conversation, $user, $content, $file);

        return $this->json($this->chatService->serializeMessage($message));
    }

    #[Route('/messages/{id}', name: 'chat_message_update', methods: ['PUT'])]
    #[IsGranted('ROLE_USER')]
    public function updateMessage(Message $message, Request $request): JsonResponse
    {
        $this->assertCsrf($request);
        /** @var User $user */
        $user = $this->getUser();
        $this->assertUserInConversation($message->getConversation(), $user);

        $payload = $request->toArray();
        $content = (string) ($payload['content'] ?? '');
        if (trim($content) === '') {
            return $this->json(['error' => 'Empty content'], Response::HTTP_BAD_REQUEST);
        }

        $this->chatService->updateMessage($message, $user, $content);

        return $this->json($this->chatService->serializeMessage($message));
    }

    #[Route('/messages/{id}', name: 'chat_message_delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_USER')]
    public function deleteMessage(Message $message, Request $request): JsonResponse
    {
        $this->assertCsrf($request);
        /** @var User $user */
        $user = $this->getUser();
        $this->assertUserInConversation($message->getConversation(), $user);

        $this->chatService->deleteMessage($message, $user);

        return $this->json($this->chatService->serializeMessage($message));
    }

    #[Route('/conversations/{id}/images', name: 'chat_conversation_images', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function recentImages(Conversation $conversation): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $this->assertUserInConversation($conversation, $user);

        $images = $this->messageRepository->findRecentImages($conversation, 24);
        $data = array_map(fn (Message $m) => $this->chatService->serializeMessage($m), $images);

        return $this->json($data);
    }

    private function assertUserInConversation(Conversation $conversation, User $user): void
    {
        if ($conversation->getAdmin()?->getId() === $user->getId()) {
            return;
        }
        if ($conversation->getParent()?->getId() === $user->getId()) {
            return;
        }

        throw $this->createAccessDeniedException();
    }

    private function assertCsrf(Request $request): void
    {
        $token = $request->headers->get('X-CSRF-TOKEN') ?? $request->request->get('_token');
        if (!$this->isCsrfTokenValid('chat_action', (string) $token)) {
            throw $this->createAccessDeniedException('Invalid CSRF token');
        }
    }
}

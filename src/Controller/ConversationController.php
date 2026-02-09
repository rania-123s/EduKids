<?php

namespace App\Controller;

use App\Entity\Conversation;
use App\Entity\User;
use App\Repository\ConversationRepository;
use App\Repository\MessageRepository;
use App\Repository\UserRepository;
use App\Service\ChatService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/chat')]
class ConversationController extends AbstractController
{
    public function __construct(
        private readonly ConversationRepository $conversationRepository,
        private readonly MessageRepository $messageRepository,
        private readonly UserRepository $userRepository,
        private readonly ChatService $chatService
    ) {
    }

    #[Route('', name: 'chat_entry', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function entry(): Response
    {
        if ($this->isGranted('ROLE_ADMIN')) {
            return $this->redirectToRoute('chat_admin');
        }

        if ($this->isGranted('ROLE_PARENT') || $this->isGranted('ROLE_USER')) {
            return $this->redirectToRoute('chat_parent');
        }

        throw $this->createAccessDeniedException();
    }

    #[Route('/admin', name: 'chat_admin', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function adminChat(): Response
    {
        return $this->render('chat/admin_chat.html.twig');
    }

    #[Route('/parent', name: 'chat_parent', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function parentChat(): Response
    {
        if ($this->isGranted('ROLE_ADMIN')) {
            return $this->redirectToRoute('chat_admin');
        }

        /** @var User $parent */
        $parent = $this->getUser();
        $admin = $this->userRepository->findOneAdmin();

        $initialConversationId = null;
        $initialConversationTitle = null;

        if ($admin instanceof User) {
            $conversation = $this->chatService->getOrCreateConversation($admin, $parent);
            $initialConversationId = $conversation->getId();
            $initialConversationTitle = $this->getDisplayName($admin);
        }

        return $this->render('chat/chat_parent.html.twig', [
            'initial_conversation_id' => $initialConversationId,
            'initial_conversation_title' => $initialConversationTitle,
        ]);
    }

    #[Route('/conversations', name: 'chat_conversations', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function listConversations(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        $search = $request->query->get('q');
        $conversations = $this->conversationRepository->findVisibleForUser($user, $search);

        $data = [];
        foreach ($conversations as $conversation) {
            $lastMessage = $this->messageRepository->findLastMessage($conversation);
            $other = $this->getOtherParticipant($conversation, $user);

            $data[] = [
                'id' => $conversation->getId(),
                'title' => $this->getDisplayName($other),
                'lastMessage' => $lastMessage?->getDeletedAt() ? 'Message supprimÃ©' : $lastMessage?->getContent(),
                'lastMessageAt' => $lastMessage?->getCreatedAt()?->format(DATE_ATOM),
            ];
        }

        return $this->json($data);
    }

    #[Route('/conversations', name: 'chat_conversation_create', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function createConversation(Request $request): JsonResponse
    {
        $this->assertCsrf($request);
        /** @var User $admin */
        $admin = $this->getUser();

        $payload = $request->toArray();
        $parentId = (int) ($payload['userId'] ?? 0);
        $parent = $this->userRepository->find($parentId);

        if (!$parent instanceof User || !in_array('ROLE_PARENT', $parent->getRoles(), true)) {
            return $this->json(['error' => 'Parent not found'], Response::HTTP_BAD_REQUEST);
        }

        $conversation = $this->chatService->getOrCreateConversation($admin, $parent);

        return $this->json([
            'id' => $conversation->getId(),
        ]);
    }

    #[Route('/conversations/{id}', name: 'chat_conversation_delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_USER')]
    public function deleteConversation(Conversation $conversation, Request $request): JsonResponse
    {
        $this->assertCsrf($request);
        /** @var User $user */
        $user = $this->getUser();
        $this->assertUserInConversation($conversation, $user);

        $this->chatService->deleteConversationForUser($conversation, $user);

        return $this->json(['ok' => true]);
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

    private function getOtherParticipant(Conversation $conversation, User $user): ?User
    {
        if ($conversation->getAdmin()?->getId() === $user->getId()) {
            return $conversation->getParent();
        }

        return $conversation->getAdmin();
    }

    private function getDisplayName(?User $user): string
    {
        if ($user === null) {
            return 'Utilisateur';
        }

        $parts = array_filter([$user->getFirstName(), $user->getLastName()]);
        return $parts ? implode(' ', $parts) : $user->getEmail();
    }

    private function assertCsrf(Request $request): void
    {
        $token = $request->headers->get('X-CSRF-TOKEN') ?? $request->request->get('_token');
        if (!$this->isCsrfTokenValid('chat_action', (string) $token)) {
            throw $this->createAccessDeniedException('Invalid CSRF token');
        }
    }
}

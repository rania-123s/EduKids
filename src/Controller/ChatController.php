<?php

namespace App\Controller;

use App\Entity\Chat;
use App\Entity\Message;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/chat')]
class ChatController extends AbstractController
{
    // Liste tous les chats principaux et leurs conversations
    #[Route('', name: 'chat_index', methods: ['GET'])]
    public function index(EntityManagerInterface $em): Response
    {
        // Trouver le chat principal (celui avec parent_id = null ou 0)
        $mainChat = $em->getRepository(Chat::class)->findOneBy(['parent_id' => null]);

        if (!$mainChat) {
            // Créer un chat principal si aucun n'existe
            $mainChat = new Chat();
            $mainChat->setParentId(0);
            $mainChat->setDateCreation(new \DateTime());
            $mainChat->setDernierMessage('');
            $mainChat->setDateDernierMessage(new \DateTime());
            $em->persist($mainChat);
            $em->flush();
        }

        // Trouver toutes les conversations (chats avec parent_id = mainChat->getId())
        $conversations = $em->getRepository(Chat::class)->findBy(['parent_id' => $mainChat->getId()]);

        return $this->render('chat/index.html.twig', [
            'mainChat' => $mainChat,
            'conversations' => $conversations,
        ]);
    }

    // Affiche la conversation et ses messages
    #[Route('/{id}', name: 'chat_show', methods: ['GET'])]
    public function show(Chat $chat): Response
    {
        // Ici $chat->getMessages() contient uniquement les messages de cette conversation
        return $this->render('chat/show.html.twig', [
            'chat' => $chat,
            'messages' => $chat->getMessages(),
        ]);
    }

    // Marquer une conversation comme lue
    #[Route('/{id}/mark-read', name: 'chat_mark_read', methods: ['POST'])]
    public function markAsRead(Chat $chat, EntityManagerInterface $em): JsonResponse
    {
        $chat->setIsRead(true);
        $em->flush();

        return new JsonResponse(['success' => true, 'message' => 'Chat marked as read']);
    }

    // Mute ou unmute une conversation
    #[Route('/{id}/mute', name: 'chat_mute', methods: ['POST'])]
    public function mute(Chat $chat, EntityManagerInterface $em): JsonResponse
    {
        $isMuted = !$chat->isMuted();
        $chat->setIsMuted($isMuted);
        $em->flush();

        return new JsonResponse(['success' => true, 'is_muted' => $isMuted, 'message' => $isMuted ? 'Chat muted' : 'Chat unmuted']);
    }

    // Supprimer une conversation
    #[Route('/{id}/delete', name: 'chat_delete', methods: ['POST'])]
    public function delete(Chat $chat, EntityManagerInterface $em): JsonResponse
    {
        $em->remove($chat);
        $em->flush();

        return new JsonResponse(['success' => true, 'message' => 'Chat deleted']);
    }
}

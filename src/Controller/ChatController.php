<?php

namespace App\Controller;

use App\Entity\Chat;
use App\Entity\Message;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
class ChatController extends AbstractController
{
    #[Route('/chat', name: 'chat_index', methods: ['GET'])]
    public function afficherChats(EntityManagerInterface $em): Response
    {
        // 1️⃣ Récupérer tous les chats principaux (parent_id = 0)
        $mainChats = $em->getRepository(Chat::class)->findBy(
            ['parent_id' => 0],
            ['date_dernier_message' => 'DESC']
        );

        return $this->render('chat/show.html.twig', [
            'conversations' => $mainChats,
        ]);
    }

    #[Route('/chat/{id}', name: 'chat_show', methods: ['GET'])]
    public function show(Chat $chat, EntityManagerInterface $em): Response
    {
        $subChats = $em->getRepository(Chat::class)->findBy(
            ['parent_id' => $chat->getId()],
            ['date_dernier_message' => 'ASC']
        );

        $messages = $chat->getMessages()->toArray();

        return $this->render('chat/show.html.twig', [
            'conversations' => array_merge([$chat], $subChats),
            'messages' => $messages,
            'current_chat' => $chat,
            'current_user_id' => $this->getUser()->getId(),
        ]);
    }

    #[Route('/chat/{id}/send', name: 'chat_send', methods: ['POST'])]
    public function sendMessage(Chat $chat, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $message = new Message();
        $message->setChat($chat);
        $message->setExpediteurId($data['sender_id']);
        $message->setContenu($data['content']);
        $message->setDateEnvoi(new \DateTime());
        $message->setLu(false);

        $em->persist($message);
        $em->flush();

        // Update chat's last message
        $chat->setDernierMessage($data['content']);
        $chat->setDateDernierMessage(new \DateTime());
        $em->flush();

        return new JsonResponse(['success' => true]);
    }

    #[Route('/chat/{id}/messages', name: 'chat_messages', methods: ['GET'])]
    public function getMessages(Chat $chat): JsonResponse
    {
        $messages = $chat->getMessages()->map(function ($message) {
            return [
                'id' => $message->getId(),
                'sender_id' => $message->getExpediteurId(),
                'content' => $message->getContenu(),
                'date' => $message->getDateEnvoi()->format('Y-m-d H:i:s'),
                'read' => $message->isLu(),
            ];
        });

        return new JsonResponse($messages->toArray());
    }

    #[Route('/chat/{id}/mark-read', name: 'chat_mark_read', methods: ['POST'])]
    public function markAsRead(Chat $chat, EntityManagerInterface $em): JsonResponse
    {
        $chat->setIsRead(true);
        $em->flush();

        return new JsonResponse(['success' => true]);
    }

    #[Route('/chat/{id}/mute', name: 'chat_mute', methods: ['POST'])]
    public function mute(Chat $chat, EntityManagerInterface $em): JsonResponse
    {
        $chat->setIsMuted(!$chat->isMuted());
        $em->flush();

        return new JsonResponse(['success' => true, 'is_muted' => $chat->isMuted()]);
    }

    #[Route('/chat/{id}/delete', name: 'chat_delete', methods: ['POST'])]
    public function delete(Chat $chat, EntityManagerInterface $em): JsonResponse
    {
        $em->remove($chat);
        $em->flush();

        return new JsonResponse(['success' => true]);
    }
}

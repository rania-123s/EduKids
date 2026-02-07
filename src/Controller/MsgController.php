<?php

namespace App\Controller;

use App\Entity\Message;
use App\Entity\Chat;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;

final class MsgController extends AbstractController
{
    #[Route('/msg', name: 'app_msg')]
    public function index(): Response
    {
        return $this->render('msg/index.html.twig', [
            'controller_name' => 'MsgController',
        ]);
    }

    // API endpoint to create a new conversation
    #[Route('/msg/conversation', name: 'msg_create_conversation', methods: ['POST'])]
    public function createConversation(Request $request, EntityManagerInterface $em): JsonResponse
    {
        // Trouver le chat principal
        $mainChat = $em->getRepository(Chat::class)->findOneBy(['parent_id' => null]);

        if (!$mainChat) {
            $mainChat = new Chat();
            $mainChat->setParentId(null);
            $mainChat->setDateCreation(new \DateTime());
            $em->persist($mainChat);
            $em->flush();
        }

        // Créer une nouvelle conversation
        $conversation = new Chat();
        $conversation->setParentId($mainChat->getId());
        $conversation->setDateCreation(new \DateTime());

        $em->persist($conversation);
        $em->flush();

        return new JsonResponse(['status' => 'Conversation créée', 'conversation_id' => $conversation->getId()]);
    }

    // API endpoint to send a message (alternative to ChatController)
    #[Route('/msg/send', name: 'msg_send_message', methods: ['POST'])]
    public function sendMessage(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data || !isset($data['chat_id'], $data['sender_id'], $data['content'])) {
            return new JsonResponse(['error' => 'Invalid data'], 400);
        }

        $chat = $em->getRepository(Chat::class)->find($data['chat_id']);
        if (!$chat) {
            return new JsonResponse(['error' => 'Chat not found'], 404);
        }

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

        return new JsonResponse(['status' => 'Message sent', 'message_id' => $message->getId()]);
    }

    // API endpoint to get all messages for a user
    #[Route('/msg/user/{userId}', name: 'msg_get_user_messages', methods: ['GET'])]
    public function getUserMessages(int $userId, EntityManagerInterface $em): JsonResponse
    {
        $messages = $em->getRepository(Message::class)->findBy(['expediteur_id' => $userId]);

        $result = array_map(function ($msg) {
            return [
                'id' => $msg->getId(),
                'chat_id' => $msg->getChat()->getId(),
                'content' => $msg->getContenu(),
                'date' => $msg->getDateEnvoi()->format('Y-m-d H:i:s'),
                'read' => $msg->isLu(),
            ];
        }, $messages);

        return new JsonResponse($result);
    }

    // API endpoint to mark message as read
    #[Route('/msg/read/{id}', name: 'msg_mark_read', methods: ['PUT'])]
    public function markAsRead(Message $message, EntityManagerInterface $em): JsonResponse
    {
        $message->setLu(true);
        $em->flush();

        return new JsonResponse(['status' => 'Message marked as read']);
    }

    // API endpoint to get messages for a specific chat
    #[Route('/msg/chat/{id}/messages', name: 'msg_chat_messages', methods: ['GET'])]
    public function getChatMessages(Chat $chat): JsonResponse
    {
        $messages = $chat->getMessages()->toArray();

        // Order by date_envoi ASC
        usort($messages, function ($a, $b) {
            return $a->getDateEnvoi() <=> $b->getDateEnvoi();
        });

        $result = array_map(function ($msg) {
            return [
                'id' => $msg->getId(),
                'sender_id' => $msg->getExpediteurId(),
                'content' => $msg->getContenu(),
                'date' => $msg->getDateEnvoi()->format('Y-m-d H:i:s'),
                'read' => $msg->isLu(),
            ];
        }, $messages);

        return new JsonResponse($result);
    }
}

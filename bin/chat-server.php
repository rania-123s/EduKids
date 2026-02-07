<?php

require __DIR__.'/../vendor/autoload.php';

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Message;
use App\Entity\Chat;

class ChatServer implements MessageComponentInterface {
    protected $clients;
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager) {
        $this->clients = new \SplObjectStorage;
        $this->entityManager = $entityManager;
    }

    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
        echo "New connection! ({$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $numRecv = count($this->clients) - 1;
        echo sprintf('Connection %d sending message "%s" to %d other connection%s' . "\n"
            , $from->resourceId, $msg, $numRecv, $numRecv == 1 ? '' : 's');

        // Parse the message
        $data = json_decode($msg, true);
        if ($data && isset($data['chat_id'], $data['sender_id'], $data['content'])) {
            // Save message to database
            $chat = $this->entityManager->getRepository(Chat::class)->find($data['chat_id']);
            if ($chat) {
                $message = new Message();
                $message->setChat($chat);
                $message->setExpediteurId($data['sender_id']);
                $message->setContenu($data['content']);
                $message->setDateEnvoi(new \DateTime());
                $message->setLu(false);

                $this->entityManager->persist($message);
                $this->entityManager->flush();

                // Update chat's last message
                $chat->setDernierMessage($data['content']);
                $chat->setDateDernierMessage(new \DateTime());
                $this->entityManager->flush();
            }
        }

        foreach ($this->clients as $client) {
            if ($from !== $client) {
                $client->send($msg);
            }
        }
    }

    public function onClose(ConnectionInterface $conn) {
        $this->clients->detach($conn);
        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "An error has occurred: {$e->getMessage()}\n";
        $conn->close();
    }
}

// Bootstrap Symfony kernel to get EntityManager
require_once __DIR__.'/../config/bootstrap.php';
$kernel = new \App\Kernel('dev', true);
$kernel->boot();
$container = $kernel->getContainer();
$entityManager = $container->get('doctrine.orm.entity_manager');

$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new ChatServer($entityManager)
        )
    ),
    8080
);

echo "Chat WebSocket server started on port 8080\n";
$server->run();

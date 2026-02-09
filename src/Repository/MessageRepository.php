<?php

namespace App\Repository;

use App\Entity\Conversation;
use App\Entity\Message;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class MessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Message::class);
    }

    /**
     * Récupérer tous les messages d'une conversation (triés par date)
     */
    public function findByConversation(Conversation $conversation): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.conversation = :conversation')
            ->setParameter('conversation', $conversation)
            ->orderBy('m.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findForConversation(Conversation $conversation, ?\DateTimeImmutable $before = null, int $limit = 50): array
    {
        $qb = $this->createQueryBuilder('m')
            ->andWhere('m.conversation = :conversation')
            ->setParameter('conversation', $conversation)
            ->orderBy('m.createdAt', 'ASC')
            ->setMaxResults($limit);

        if ($before !== null) {
            $qb->andWhere('m.createdAt < :before')
                ->setParameter('before', $before);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Compter les messages non lus d'une conversation pour un utilisateur
     * (messages non lus dont l'expéditeur n'est pas cet utilisateur)
     */
    public function countUnreadInConversationForUser(Conversation $conversation, User $user): int
    {
        return (int) $this->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->andWhere('m.conversation = :conversation')
            ->andWhere('m.isRead = false')
            ->andWhere('m.sender != :user')
            ->setParameter('conversation', $conversation)
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Dernier message d'une conversation
     */
    public function findLastMessage(Conversation $conversation): ?Message
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.conversation = :conversation')
            ->setParameter('conversation', $conversation)
            ->orderBy('m.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findRecentImages(Conversation $conversation, int $limit = 12): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.conversation = :conversation')
            ->andWhere('m.type = :type')
            ->andWhere('m.filePath IS NOT NULL')
            ->setParameter('conversation', $conversation)
            ->setParameter('type', 'image')
            ->orderBy('m.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Marquer comme lus tous les messages d'une conversation
     * reçus par un utilisateur (donc envoyés par l'autre)
     */
    public function markConversationAsReadForUser(Conversation $conversation, User $user): int
    {
        // UPDATE Message m SET m.isRead = true WHERE ...
        return $this->createQueryBuilder('m')
            ->update()
            ->set('m.isRead', ':read')
            ->where('m.conversation = :conversation')
            ->andWhere('m.isRead = false')
            ->andWhere('m.sender != :user')
            ->setParameter('conversation', $conversation)
            ->setParameter('user', $user)
            ->setParameter('read', true)
            ->getQuery()
            ->execute();
    }

    /**
     * Récupérer les messages non lus pour un admin (toutes conversations confondues)
     * -> messages non lus envoyés par des parents
     */
    public function findUnreadForAdmin(int $adminId): array
    {
        return $this->createQueryBuilder('m')
            ->join('m.conversation', 'c')
            ->join('c.admin', 'a')
            ->where('a.id = :adminId')
            ->andWhere('m.isRead = false')
            ->andWhere('m.sender != a') // envoyé par le parent
            ->setParameter('adminId', $adminId)
            ->orderBy('m.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupérer les messages non lus pour un parent (toutes conversations confondues)
     * -> messages non lus envoyés par l'admin
     */
    public function findUnreadForParent(int $parentId): array
    {
        return $this->createQueryBuilder('m')
            ->join('m.conversation', 'c')
            ->join('c.parent', 'p')
            ->where('p.id = :parentId')
            ->andWhere('m.isRead = false')
            ->andWhere('m.sender != p') // envoyé par l'admin
            ->setParameter('parentId', $parentId)
            ->orderBy('m.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}

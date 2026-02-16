<?php

namespace App\Repository;

use App\Entity\Conversation;
use App\Entity\Message;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class MessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Message::class);
    }

    /**
     * @return Message[]
     */
    public function findPaginatedForConversation(Conversation $conversation, int $page = 1, int $limit = 30): array
    {
        $page = max(1, $page);
        $limit = max(1, min($limit, 100));
        $offset = ($page - 1) * $limit;

        $messages = $this->createQueryBuilder('m')
            ->andWhere('m.conversation = :conversation')
            ->setParameter('conversation', $conversation)
            ->orderBy('m.createdAt', 'DESC')
            ->addOrderBy('m.id', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return array_reverse($messages);
    }

    /**
     * @return Message[]
     */
    public function findForConversation(Conversation $conversation, ?\DateTimeImmutable $before = null, int $limit = 50): array
    {
        $limit = max(1, min($limit, 100));
        $qb = $this->createQueryBuilder('m')
            ->andWhere('m.conversation = :conversation')
            ->setParameter('conversation', $conversation)
            ->orderBy('m.createdAt', 'ASC')
            ->addOrderBy('m.id', 'ASC')
            ->setMaxResults($limit);

        if ($before !== null) {
            $qb->andWhere('m.createdAt < :before')
                ->setParameter('before', $before);
        }

        return $qb->getQuery()->getResult();
    }

    public function findLastMessage(Conversation $conversation): ?Message
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.conversation = :conversation')
            ->setParameter('conversation', $conversation)
            ->orderBy('m.createdAt', 'DESC')
            ->addOrderBy('m.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return Message[]
     */
    public function findRecentImages(Conversation $conversation, int $limit = 12): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.conversation = :conversation')
            ->andWhere('m.deletedAt IS NULL')
            ->andWhere('(
                EXISTS (
                    SELECT 1 FROM App\Entity\MessageAttachment ma
                    WHERE ma.message = m AND ma.isImage = :isImage
                )
                OR (m.type = :legacyType AND m.filePath IS NOT NULL)
            )')
            ->setParameter('conversation', $conversation)
            ->setParameter('isImage', true)
            ->setParameter('legacyType', 'image')
            ->orderBy('m.createdAt', 'DESC')
            ->addOrderBy('m.id', 'DESC')
            ->setMaxResults(max(1, min($limit, 100)))
            ->getQuery()
            ->getResult();
    }
}

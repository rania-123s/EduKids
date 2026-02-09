<?php

namespace App\Repository;

use App\Entity\Conversation;
use App\Entity\Message;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ConversationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Conversation::class);
    }

    //  Trouver conversations d'un admin (objet User)
    public function findByAdmin(User $admin): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.admin = :admin')
            ->setParameter('admin', $admin)
            ->orderBy('c.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    //  Trouver conversations d'un admin (ID)
    public function getForAdmin(int $adminId): array
    {
        return $this->createQueryBuilder('c')
            ->join('c.admin', 'a')
            ->andWhere('a.id = :adminId')
            ->setParameter('adminId', $adminId)
            ->orderBy('c.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    //  Trouver conversations d'un parent (objet User)
    public function findByParent(User $parent): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.parent = :parent')
            ->setParameter('parent', $parent)
            ->orderBy('c.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    //  Trouver conversations d'un parent (ID)
    public function getForParent(int $parentId): array
    {
        return $this->createQueryBuilder('c')
            ->join('c.parent', 'p')
            ->andWhere('p.id = :parentId')
            ->setParameter('parentId', $parentId)
            ->orderBy('c.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    // Compter les messages non lus pour un admin
    public function countUnreadMessagesForAdmin(int $adminId): int
    {
        return (int) $this->createQueryBuilder('c')
            ->select('COUNT(m.id)')
            ->leftJoin('c.messages', 'm')
            ->leftJoin('m.sender', 's')
            ->leftJoin('c.admin', 'a')
            ->where('a.id = :adminId')
            ->andWhere('m.isRead = false')
            ->andWhere('s.id != :adminId') // expéditeur ≠ admin
            ->setParameter('adminId', $adminId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    //   Compter les messages non lus pour un parent
    public function countUnreadMessagesForParent(int $parentId): int
    {
        return (int) $this->createQueryBuilder('c')
            ->select('COUNT(m.id)')
            ->leftJoin('c.messages', 'm')
            ->leftJoin('m.sender', 's')
            ->leftJoin('c.parent', 'p')
            ->where('p.id = :parentId')
            ->andWhere('m.isRead = false')
            ->andWhere('s.id != :parentId') // expéditeur ≠ parent
            ->setParameter('parentId', $parentId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    //  Stats mensuelles des conversations (créées ce mois / mois dernier) pour un admin
    public function getMonthlyStatsForAdmin(int $adminId): array
    {
        $currentMonth = new \DateTimeImmutable('first day of this month');
        $lastMonth = new \DateTimeImmutable('first day of last month');

        $currentMonthCount = $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->leftJoin('c.admin', 'a')
            ->where('a.id = :adminId')
            ->andWhere('c.createdAt >= :startDate')
            ->setParameter('adminId', $adminId)
            ->setParameter('startDate', $currentMonth)
            ->getQuery()
            ->getSingleScalarResult();

        $lastMonthCount = $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->leftJoin('c.admin', 'a')
            ->where('a.id = :adminId')
            ->andWhere('c.createdAt >= :startDate AND c.createdAt < :endDate')
            ->setParameter('adminId', $adminId)
            ->setParameter('startDate', $lastMonth)
            ->setParameter('endDate', $currentMonth)
            ->getQuery()
            ->getSingleScalarResult();

        return [
            'current_month' => (int) $currentMonthCount,
            'last_month' => (int) $lastMonthCount,
            'change_percent' => $lastMonthCount > 0
                ? round((($currentMonthCount - $lastMonthCount) / $lastMonthCount) * 100, 1)
                : ((int)$currentMonthCount > 0 ? 100.0 : 0.0),
        ];
    }

    //  Trouver la conversation entre un admin et un parent
    public function findBetweenAdminAndParent(User $admin, User $parent): ?Conversation
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.admin = :admin')
            ->andWhere('c.parent = :parent')
            ->setParameter('admin', $admin)
            ->setParameter('parent', $parent)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Conversations visibles pour un utilisateur (filtre deletedAt par participant).
     * Optionnel: filtrage par nom/prenom du parent.
     */
    public function findVisibleForUser(User $user, ?string $search = null): array
    {
        $subQb = $this->getEntityManager()->createQueryBuilder()
            ->select('MAX(m2.id)')
            ->from(Message::class, 'm2')
            ->where('m2.conversation = c');

        $qb = $this->createQueryBuilder('c')
            ->innerJoin('c.participants', 'p', 'WITH', 'p.user = :user')
            ->leftJoin('c.messages', 'lm', 'WITH', 'lm.id = (' . $subQb->getDQL() . ')')
            ->addSelect('p', 'lm')
            ->setParameter('user', $user);

        if ($search !== null && trim($search) !== '') {
            $qb->leftJoin('c.parent', 'parent')
                ->andWhere('LOWER(parent.firstName) LIKE :q OR LOWER(parent.lastName) LIKE :q')
                ->setParameter('q', '%' . mb_strtolower(trim($search)) . '%');
        }

        $qb->andWhere('p.deletedAt IS NULL OR (lm.createdAt IS NOT NULL AND lm.createdAt > p.deletedAt)')
            ->orderBy('lm.createdAt', 'DESC');

        return $qb->getQuery()->getResult();
    }
}

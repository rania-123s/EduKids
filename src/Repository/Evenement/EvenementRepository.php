<?php

namespace App\Repository\Evenement;

use App\Entity\Evenement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Evenement>
 */
class EvenementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Evenement::class);
    }

    public function save(Evenement $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Evenement $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @return array{totalEvents: int, totalLikes: int, totalDislikes: int, totalFavorites: int}
     */
    public function getStats(): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = 'SELECT
            COUNT(*) AS total_events,
            COALESCE(SUM(likes_count), 0) AS total_likes,
            COALESCE(SUM(dislikes_count), 0) AS total_dislikes,
            COALESCE(SUM(favorites_count), 0) AS total_favorites
            FROM evenement';
        $row = $conn->executeQuery($sql)->fetchAssociative();
        return [
            'totalEvents' => (int) ($row['total_events'] ?? 0),
            'totalLikes' => (int) ($row['total_likes'] ?? 0),
            'totalDislikes' => (int) ($row['total_dislikes'] ?? 0),
            'totalFavorites' => (int) ($row['total_favorites'] ?? 0),
        ];
    }

    /**
     * @return Evenement[]
     */
    public function findTopByLikes(int $limit = 10): array
    {
        return $this->createQueryBuilder('e')
            ->orderBy('e.likesCount', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Evenement[]
     */
    public function findTopByDislikes(int $limit = 10): array
    {
        return $this->createQueryBuilder('e')
            ->orderBy('e.dislikesCount', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Evenement[]
     */
    public function findTopByFavorites(int $limit = 10): array
    {
        return $this->createQueryBuilder('e')
            ->orderBy('e.favoritesCount', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}


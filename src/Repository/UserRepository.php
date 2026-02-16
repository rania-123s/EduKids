<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    //    /**
    //     * @return User[] Returns an array of User objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('u')
    //            ->andWhere('u.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('u.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    /**
     * Search users by first name, last name, or email
     *
     * @param string|null $searchTerm The search term (null or empty string returns all users)
     * @return User[] Returns an array of User objects
     */
    public function searchUsers(?string $searchTerm = null): array
    {
        $qb = $this->createQueryBuilder('u');

        if ($searchTerm && trim($searchTerm) !== '') {
            $searchTerm = '%' . strtolower(trim($searchTerm)) . '%';
            
            $qb->where('LOWER(u.firstName) LIKE :search')
               ->orWhere('LOWER(u.lastName) LIKE :search')
               ->orWhere('LOWER(u.email) LIKE :search')
               ->setParameter('search', $searchTerm);
        }

        return $qb->orderBy('u.id', 'DESC')
                  ->getQuery()
                  ->getResult();
    }

    public function findOneAdmin(): ?User
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.roles LIKE :role')
            ->setParameter('role', '%"ROLE_ADMIN"%')
            ->orderBy('u.id', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return User[]
     */
    public function searchParentsByName(string $searchTerm, ?int $excludeUserId = null, int $limit = 20): array
    {
        $query = mb_strtolower(trim($searchTerm));
        if ($query === '') {
            return [];
        }

        $qb = $this->createQueryBuilder('u')
            ->andWhere('u.roles LIKE :roleParent')
            ->andWhere('
                LOWER(COALESCE(u.firstName, \'\')) LIKE :query
                OR LOWER(COALESCE(u.lastName, \'\')) LIKE :query
                OR LOWER(COALESCE(u.email, \'\')) LIKE :query
            ')
            ->setParameter('roleParent', '%"ROLE_PARENT"%')
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('u.firstName', 'ASC')
            ->addOrderBy('u.lastName', 'ASC')
            ->setMaxResults(max(1, min($limit, 50)));

        if ($excludeUserId !== null) {
            $qb->andWhere('u.id != :excludeUserId')
                ->setParameter('excludeUserId', $excludeUserId);
        }

        return $qb->getQuery()->getResult();
    }
}

<?php

namespace App\Repository;

use App\Entity\UserBook;
use App\Entity\User;
use App\Entity\Book;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserBook>
 */
class UserBookRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserBook::class);
    }

    /**
     * @return UserBook[] Returns all books for a given user
     */
    public function findByUser(User $user): array
    {
        return $this->getEntityManager()
            ->createQuery('
            SELECT b
            FROM App\Entity\Book b
            JOIN b.userBooks ub
            WHERE ub.user = :user
        ')
            ->setParameter('user', $user)
            ->getResult();
    }

    /**
     * @return UserBook[] Returns all users that have a given book
     */
    public function findByBook(Book $book): array
    {
        return $this->createQueryBuilder('ub')
            ->andWhere('ub.book = :book')
            ->setParameter('book', $book)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return UserBook[] Returns all books for a user filtered by status
     */
    public function findByUserAndStatus(User $user, string $status): array
    {
        return $this->createQueryBuilder('ub')
            ->andWhere('ub.user = :user')
            ->andWhere('ub.status = :status')
            ->setParameter('user', $user)
            ->setParameter('status', $status)
            ->getQuery()
            ->getResult();
    }
}
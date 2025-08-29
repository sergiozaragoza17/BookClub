<?php

namespace App\Repository;

use App\Entity\Book;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Book>
 *
 * @method Book|null find($id, $lockMode = null, $lockVersion = null)
 * @method Book|null findOneBy(array $criteria, array $orderBy = null)
 * @method Book[]    findAll()
 * @method Book[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BookRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Book::class);
    }

    public function findTopBooksByFiveStarReviews(int $limit = 3)
    {
        return $this->createQueryBuilder('b')
            ->select('b, COUNT(r.id) AS fiveStarCount')
            ->leftJoin('b.reviews', 'r', 'WITH', 'r.status = :approved AND r.rating = 5')
            ->setParameter('approved', 'approved')
            ->groupBy('b.id')
            ->orderBy('fiveStarCount', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}

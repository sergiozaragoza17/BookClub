<?php

namespace App\Repository;

use App\Entity\Book;
use App\Entity\Club;
use App\Entity\ClubBook;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ClubBook>
 *
 * @method ClubBook|null find($id, $lockMode = null, $lockVersion = null)
 * @method ClubBook|null findOneBy(array $criteria, array $orderBy = null)
 * @method ClubBook[]    findAll()
 * @method ClubBook[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ClubBookRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ClubBook::class);
    }

    public function findClubsByBookAndMember(Book $book, User $user): array
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->select('c')
            ->from('App\Entity\Club', 'c')
            ->innerJoin('c.members', 'm')
            ->innerJoin('c.clubBooks', 'cb')
            ->where('cb.book = :book')
            ->andWhere('m = :user')
            ->setParameter('book', $book)
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();
    }
}

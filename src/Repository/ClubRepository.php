<?php

namespace App\Repository;

use App\Entity\Club;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Club>
 *
 * @method Club|null find($id, $lockMode = null, $lockVersion = null)
 * @method Club|null findOneBy(array $criteria, array $orderBy = null)
 * @method Club[]    findAll()
 * @method Club[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ClubRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Club::class);
    }

    public function findByMember(User $user): array
    {
        return $this->createQueryBuilder('c')
            ->innerJoin('c.members', 'm')
            ->andWhere('m = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();
    }
}

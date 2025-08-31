<?php

namespace App\Repository;

use App\Entity\ClubPost;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ClubPost>
 *
 * @method ClubPost|null find($id, $lockMode = null, $lockVersion = null)
 * @method ClubPost|null findOneBy(array $criteria, array $orderBy = null)
 * @method ClubPost[]    findAll()
 * @method ClubPost[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ClubPostRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ClubPost::class);
    }
}
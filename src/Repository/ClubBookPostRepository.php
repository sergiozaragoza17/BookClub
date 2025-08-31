<?php

namespace App\Repository;

use App\Entity\ClubBookPost;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ClubBookPost>
 *
 * @method ClubBookPost|null find($id, $lockMode = null, $lockVersion = null)
 * @method ClubBookPost|null findOneBy(array $criteria, array $orderBy = null)
 * @method ClubBookPost[]    findAll()
 * @method ClubBookPost[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ClubBookPostRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ClubBookPost::class);
    }
}
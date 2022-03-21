<?php

namespace App\Repository;

use App\Entity\RuleFilter;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method RuleFilter|null find($id, $lockMode = null, $lockVersion = null)
 * @method RuleFilter|null findOneBy(array $criteria, array $orderBy = null)
 * @method RuleFilter[]    findAll()
 * @method RuleFilter[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RuleFilterRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RuleFilter::class);
    }

    // /**
    //  * @return RuleFilter[] Returns an array of RuleFilter objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('r.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?RuleFilter
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}

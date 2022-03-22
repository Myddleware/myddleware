<?php

namespace App\Repository;

use App\Entity\RuleOrder;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method RuleOrder|null find($id, $lockMode = null, $lockVersion = null)
 * @method RuleOrder|null findOneBy(array $criteria, array $orderBy = null)
 * @method RuleOrder[]    findAll()
 * @method RuleOrder[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RuleOrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RuleOrder::class);
    }

    // /**
    //  * @return RuleOrder[] Returns an array of RuleOrder objects
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
    public function findOneBySomeField($value): ?RuleOrder
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
    public function deleteAll()
    {
        return $this->createQueryBuilder('r')
            ->delete()
            ->getQuery()
            ->execute();
    }
}

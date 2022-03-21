<?php

namespace App\Repository;

use App\Entity\RuleAudit;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method RuleAudit|null find($id, $lockMode = null, $lockVersion = null)
 * @method RuleAudit|null findOneBy(array $criteria, array $orderBy = null)
 * @method RuleAudit[]    findAll()
 * @method RuleAudit[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RuleAuditRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RuleAudit::class);
    }

    // /**
    //  * @return RuleAudit[] Returns an array of RuleAudit objects
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
    public function findOneBySomeField($value): ?RuleAudit
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

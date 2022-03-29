<?php

namespace App\Repository;

use App\Entity\RuleParamAudit;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method RuleParamAudit|null find($id, $lockMode = null, $lockVersion = null)
 * @method RuleParamAudit|null findOneBy(array $criteria, array $orderBy = null)
 * @method RuleParamAudit[]    findAll()
 * @method RuleParamAudit[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RuleParamAuditRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RuleParamAudit::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(RuleParamAudit $entity, bool $flush = true): void
    {
        $this->_em->persist($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function remove(RuleParamAudit $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    // /**
    //  * @return RuleParamAudit[] Returns an array of RuleParamAudit objects
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
    public function findOneBySomeField($value): ?RuleParamAudit
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

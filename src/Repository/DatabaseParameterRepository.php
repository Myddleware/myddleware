<?php

namespace App\Repository;

use App\Entity\DatabaseParameter;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method DatabaseParameter|null find($id, $lockMode = null, $lockVersion = null)
 * @method DatabaseParameter|null findOneBy(array $criteria, array $orderBy = null)
 * @method DatabaseParameter[]    findAll()
 * @method DatabaseParameter[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DatabaseParameterRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Database::class);
    }

    // /**
    //  * @return DatabaseParameter[] Returns an array of Database objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('d.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Database
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}

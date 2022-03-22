<?php

namespace App\Repository;

use App\Entity\DocumentRelationship;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method DocumentRelationship|null find($id, $lockMode = null, $lockVersion = null)
 * @method DocumentRelationship|null findOneBy(array $criteria, array $orderBy = null)
 * @method DocumentRelationship[]    findAll()
 * @method DocumentRelationship[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DocumentRelationshipRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DocumentRelationship::class);
    }

    // /**
    //  * @return DocumentRelationship[] Returns an array of DocumentRelationship objects
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
    public function findOneBySomeField($value): ?DocumentRelationship
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

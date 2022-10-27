<?php

namespace App\Repository;

use App\Entity\InternalListValue;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<InternalListValue>
 *
 * @method InternalListValue|null find($id, $lockMode = null, $lockVersion = null)
 * @method InternalListValue|null findOneBy(array $criteria, array $orderBy = null)
 * @method InternalListValue[]    findAll()
 * @method InternalListValue[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class InternalListValueRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, InternalListValue::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(InternalListValue $entity, bool $flush = true): void
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
    public function remove(InternalListValue $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    public function searchRecords($params): array
    {
        return $this->createQueryBuilder('internal_list_value')
            ->Where('internal_list_value.reference >= :dateref')
            ->andWhere('internal_list_value.listId = :module')
            ->getQuery()
            ->setMaxResults((int) $params['limit'])
            ->setParameter('module', $params['module'])
            ->setParameter('dateref', $params['date_ref'])
            ->getResult();
    }

    // /**
    //  * @return InternalListValue[] Returns an array of InternalListValue objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('i.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?InternalListValue
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}

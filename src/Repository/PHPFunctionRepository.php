<?php

namespace App\Repository;

use App\Entity\PHPFunction;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PHPFunction>
 *
 * @method PHPFunction|null find($id, $lockMode = null, $lockVersion = null)
 * @method PHPFunction|null findOneBy(array $criteria, array $orderBy = null)
 * @method PHPFunction[]    findAll()
 * @method PHPFunction[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PHPFunctionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PHPFunction::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(PHPFunction $entity, bool $flush = true): void
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
    public function remove(PHPFunction $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    // /**
    //  * @return PHPFunction[] Returns an array of PHPFunction objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('p.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?PHPFunction
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}

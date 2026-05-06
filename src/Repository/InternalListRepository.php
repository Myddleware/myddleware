<?php

namespace App\Repository;

use App\Entity\InternalList;
use App\Service\DebugLogger;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<InternalList>
 *
 * @method InternalList|null find($id, $lockMode = null, $lockVersion = null)
 * @method InternalList|null findOneBy(array $criteria, array $orderBy = null)
 * @method InternalList[]    findAll()
 * @method InternalList[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class InternalListRepository extends ServiceEntityRepository
{
    private DebugLogger $debugLogger;

    public function __construct(ManagerRegistry $registry, DebugLogger $debugLogger)
    {
        parent::__construct($registry, InternalList::class);
        $this->debugLogger = $debugLogger;
    }

    public function add(InternalList $entity, bool $flush = true): void
    {
        $this->debugLogger->logStart(__CLASS__, __FUNCTION__, ['entity' => $entity, 'flush' => $flush]);
        try {
            $this->_em->persist($entity);
            if ($flush) {
                $this->_em->flush();
            }
        } finally {
            $this->debugLogger->logEnd(__CLASS__, __FUNCTION__);
        }
    }

    public function remove(InternalList $entity, bool $flush = true): void
    {
        $this->debugLogger->logStart(__CLASS__, __FUNCTION__, ['entity' => $entity, 'flush' => $flush]);
        try {
            $this->_em->remove($entity);
            if ($flush) {
                $this->_em->flush();
            }
        } finally {
            $this->debugLogger->logEnd(__CLASS__, __FUNCTION__);
        }
    }
}

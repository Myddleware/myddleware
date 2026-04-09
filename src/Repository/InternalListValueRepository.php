<?php

namespace App\Repository;

use App\Entity\InternalListValue;
use App\Service\DebugLogger;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

class InternalListValueRepository extends ServiceEntityRepository
{
    private DebugLogger $debugLogger;

    public function __construct(ManagerRegistry $registry, DebugLogger $debugLogger)
    {
        parent::__construct($registry, InternalListValue::class);
        $this->debugLogger = $debugLogger;
    }

    public function add(InternalListValue $entity, bool $flush = true): void
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

    public function remove(InternalListValue $entity, bool $flush = true): void
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

    public function searchRecords($params): array
    {
        $this->debugLogger->logStart(__CLASS__, __FUNCTION__, ['params' => $params]);
        $__debugReturn = null;
        try {
            return $__debugReturn = $this->createQueryBuilder('internal_list_value')
                ->Where('internal_list_value.reference > :dateref')
                ->andWhere('internal_list_value.listId = :module')
                ->getQuery()
                ->setMaxResults((int) $params['limit'])
                ->setParameter('module', $params['module'])
                ->setParameter('dateref', $params['date_ref'])
                ->getResult();
        } finally {
            $this->debugLogger->logEnd(__CLASS__, __FUNCTION__, $__debugReturn);
        }
    }
}

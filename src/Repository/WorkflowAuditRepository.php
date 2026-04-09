<?php

namespace App\Repository;

use App\Entity\WorkflowAudit;
use App\Service\DebugLogger;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<WorkflowAudit>
 *
 * @method WorkflowAudit|null find($id, $lockMode = null, $lockVersion = null)
 * @method WorkflowAudit|null findOneBy(array $criteria, array $orderBy = null)
 * @method WorkflowAudit[]    findAll()
 * @method WorkflowAudit[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class WorkflowAuditRepository extends ServiceEntityRepository
{
    private DebugLogger $debugLogger;

    public function __construct(ManagerRegistry $registry, DebugLogger $debugLogger)
    {
        parent::__construct($registry, WorkflowAudit::class);
        $this->debugLogger = $debugLogger;
    }

    public function add(WorkflowAudit $entity, bool $flush = true): void
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

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function remove(WorkflowAudit $entity, bool $flush = true): void
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

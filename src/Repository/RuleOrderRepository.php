<?php

namespace App\Repository;

use App\Entity\RuleOrder;
use App\Service\DebugLogger;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class RuleOrderRepository extends ServiceEntityRepository
{
    private DebugLogger $debugLogger;

    public function __construct(ManagerRegistry $registry, DebugLogger $debugLogger)
    {
        parent::__construct($registry, RuleOrder::class);
        $this->debugLogger = $debugLogger;
    }

    public function deleteAll()
    {
        $this->debugLogger->logStart(__CLASS__, __FUNCTION__, []);
        $__debugReturn = null;
        try {
            return $__debugReturn = $this->createQueryBuilder('r')
                ->delete()
                ->getQuery()
                ->execute();
        } finally {
            $this->debugLogger->logEnd(__CLASS__, __FUNCTION__, $__debugReturn);
        }
    }
}

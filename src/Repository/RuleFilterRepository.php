<?php

namespace App\Repository;

use App\Entity\RuleFilter;
use App\Service\DebugLogger;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class RuleFilterRepository extends ServiceEntityRepository
{
    private DebugLogger $debugLogger;

    public function __construct(ManagerRegistry $registry, DebugLogger $debugLogger)
    {
        parent::__construct($registry, RuleFilter::class);
        $this->debugLogger = $debugLogger;
    }
}

<?php

namespace App\Repository;

use App\Entity\DatabaseParameter;
use App\Service\DebugLogger;
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
    private DebugLogger $debugLogger;

    public function __construct(ManagerRegistry $registry, DebugLogger $debugLogger)
    {
        parent::__construct($registry, DatabaseParameter::class);
        $this->debugLogger = $debugLogger;
    }
}

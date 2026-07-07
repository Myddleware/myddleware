<?php

namespace App\Repository;

use App\Entity\DocumentRelationship;
use App\Service\DebugLogger;
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
    private DebugLogger $debugLogger;

    public function __construct(ManagerRegistry $registry, DebugLogger $debugLogger)
    {
        parent::__construct($registry, DocumentRelationship::class);
        $this->debugLogger = $debugLogger;
    }
}

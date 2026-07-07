<?php
/*********************************************************************************
 * This file is part of Myddleware.

 * @package Myddleware
 * @copyright Copyright (C) 2013 - 2015  Stéphane Faure - CRMconsult EURL
 * @copyright Copyright (C) 2015 - 2016  Stéphane Faure - Myddleware ltd - contact@myddleware.com
 * @link http://www.myddleware.com

 This file is part of Myddleware.

 Myddleware is free software: you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation, either version 3 of the License, or
 (at your option) any later version.

 Myddleware is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with Myddleware.  If not, see <http://www.gnu.org/licenses/>.
*********************************************************************************/

namespace App\Repository;

use Doctrine\ORM\Query;
use App\Entity\Solution;
use App\Entity\Connector;
use App\Service\DebugLogger;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @method Connector|null find($id, $lockMode = null, $lockVersion = null)
 * @method Connector|null findOneBy(array $criteria, array $orderBy = null)
 * @method Connector[]    findAll()
 * @method Connector[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ConnectorRepository extends ServiceEntityRepository
{
    private DebugLogger $debugLogger;

    public function __construct(ManagerRegistry $registry, DebugLogger $debugLogger)
    {
        parent::__construct($registry, Connector::class);
        $this->debugLogger = $debugLogger;
    }

    public function findAllConnectorByUser($id, $type)
    {
        $this->debugLogger->logStart(__CLASS__, __FUNCTION__, ['id' => $id, 'type' => $type]);
        $__debugReturn = null;
        try {
            $qb = $this->createQueryBuilder('c');
            $qb->select('c.id as id_connector, c.name')
             ->leftJoin('c.solution', 's')
             ->where('c.createdBy = :user_id')
             ->andWhere('s.'.$type.' = 1')
             ->andWhere('s.active = 1')
             ->setParameter('user_id', $id);

            return $__debugReturn = $qb->getQuery()
                      ->getResult();
        } finally {
            $this->debugLogger->logEnd(__CLASS__, __FUNCTION__, $__debugReturn);
        }
    }

    public function findListConnectorByUser($is_support, $id): Query
    {
        $this->debugLogger->logStart(__CLASS__, __FUNCTION__, ['is_support' => $is_support, 'id' => $id]);
        $__debugReturn = null;
        try {
            $qb = $this->createQueryBuilder('c');

            $qb->innerJoin('c.solution', 'sol')
                ->addSelect('sol.name solution');

            if (false === $is_support) {
                $qb->where('c.createdBy = :user_id AND c.deleted = 0')
                   ->setParameter('user_id', $id);
            } else {
                $qb->where('c.deleted = 0');
            }

            $qb->orderBy('c.id', 'DESC');

            return $__debugReturn = $qb->getQuery();
        } finally {
            $this->debugLogger->logEnd(__CLASS__, __FUNCTION__, $__debugReturn);
        }
    }

    public function existsActiveName(string $name, ?int $excludeId = null): bool
    {
        $this->debugLogger->logStart(__CLASS__, __FUNCTION__, ['name' => $name, 'excludeId' => $excludeId]);
        $__debugReturn = null;
        try {
            $qb = $this->createQueryBuilder('c')
                ->select('1')
                ->where('LOWER(c.name) = LOWER(:name)')
                ->andWhere('c.deleted = 0')
                ->setMaxResults(1)
                ->setParameter('name', trim($name));

            if ($excludeId) {
                $qb->andWhere('c.id != :id')->setParameter('id', $excludeId);
            }
            return $__debugReturn = (bool) $qb->getQuery()->getOneOrNullResult();
        } finally {
            $this->debugLogger->logEnd(__CLASS__, __FUNCTION__, $__debugReturn);
        }
    }

    public function findActiveBySolution(Solution $solution): array
    {
        $this->debugLogger->logStart(__CLASS__, __FUNCTION__, ['solution' => $solution]);
        $__debugReturn = null;
        try {
            return $__debugReturn = $this->createQueryBuilder('c')
                ->andWhere('c.solution = :solution')
                ->andWhere('c.deleted = 0')
                ->setParameter('solution', $solution)
                ->orderBy('c.name', 'ASC')
                ->getQuery()
                ->getResult();
        } finally {
            $this->debugLogger->logEnd(__CLASS__, __FUNCTION__, $__debugReturn);
        }
    }
}

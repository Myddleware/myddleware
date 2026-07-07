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

use App\Entity\Solution;
use App\Service\DebugLogger;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class SolutionRepository extends ServiceEntityRepository
{
    private DebugLogger $debugLogger;

    public function __construct(ManagerRegistry $registry, DebugLogger $debugLogger)
    {
        parent::__construct($registry, Solution::class);
        $this->debugLogger = $debugLogger;
    }

    // Liste des solutions actives
    public function solutionActive()
    {
        $this->debugLogger->logStart(__CLASS__, __FUNCTION__);
        $__debugReturn = null;
        try {
            return $__debugReturn = $this
              ->createQueryBuilder('s')
              ->select('s')
              ->where('s.active = :active')
              ->setParameter('active', 1)
              ->getQuery()
              ->getResult();
        } finally {
            $this->debugLogger->logEnd(__CLASS__, __FUNCTION__, $__debugReturn);
        }
    }

    // Liste des solutions en fonction des connecteurs
    public function solutionConnector($type, $is_support, $id)
    {
        $this->debugLogger->logStart(__CLASS__, __FUNCTION__, ['type' => $type, 'is_support' => $is_support, 'id' => $id]);
        $__debugReturn = null;
        try {
            $qb = $this->createQueryBuilder('s');

            $field = (('target' == $type) ? 'target' : 'source');

            $qb->select('s', 'c')
             ->innerJoin('s.connector', 'c');

            if (false === $is_support) {
                $qb->where('s.active = :active AND s.'.$field.' = :type AND c.createdBy = :user_id')
                   ->setParameter('active', 1)
                   ->setParameter('type', 1)
                   ->setParameter('user_id', $id);
            } else {
                $qb->where('s.active = :active AND s.'.$field.' = :type')
                   ->setParameter('active', 1)
                   ->setParameter('type', 1);
            }

            $qb->orderBy('s.name', 'ASC');

            return $__debugReturn = $qb->getQuery()
                      ->getResult();
        } finally {
            $this->debugLogger->logEnd(__CLASS__, __FUNCTION__, $__debugReturn);
        }
    }

    // Liste des solutions en fonction des types
    public function solutionConnectorType($type)
    {
        $this->debugLogger->logStart(__CLASS__, __FUNCTION__, ['type' => $type]);
        $__debugReturn = null;
        try {
            $qb = $this->createQueryBuilder('s');

            $field = (('target' == $type) ? 'target' : 'source');

            $qb->select('s.name')
             ->where('s.active = :active AND s.'.$field.' = :type')
             ->setParameter('active', 1)
             ->setParameter('type', 1)
             ->groupBy('s.name')
             ->orderBy('s.name', 'ASC');

            return $__debugReturn = $qb->getQuery()
                      ->getResult();
        } finally {
            $this->debugLogger->logEnd(__CLASS__, __FUNCTION__, $__debugReturn);
        }
    }

    public function resolveName($val): ?string
    {
        $this->debugLogger->logStart(__CLASS__, __FUNCTION__, ['val' => $val]);
        $__debugReturn = null;
        try {
            if (!$val) return $__debugReturn = null;
            if (!is_numeric($val)) return $__debugReturn = (string) $val;
            $solution = $this->find((int) $val);
            if ($solution && method_exists($solution, 'getName')) {
                return $__debugReturn = (string) $solution->getName();
            }

            return $__debugReturn = null;
        } finally {
            $this->debugLogger->logEnd(__CLASS__, __FUNCTION__, $__debugReturn);
        }
    }
}

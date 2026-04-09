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

use App\Entity\Job;

use App\Manager\HomeManager;
use App\Service\DebugLogger;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;

class JobRepository extends ServiceEntityRepository
{
    private DebugLogger $debugLogger;

    public function __construct(ManagerRegistry $registry, DebugLogger $debugLogger)
    {
        parent::__construct($registry, Job::class);
        $this->debugLogger = $debugLogger;
    }

    public function findJobStarted($begin): ?Job
    {
        $this->debugLogger->logStart(__CLASS__, __FUNCTION__, ['begin' => $begin]);
        $__debugReturn = null;
        try {
            return $__debugReturn = $this->createQueryBuilder('j')
                ->select('j')
                ->where('j.status = :status')
                ->andWhere('j.begin < :timeLimit')
                ->setParameter('status', 'Start')
                ->setParameter('timeLimit', $begin)
                ->getQuery()
                ->setMaxResults(1)
                ->getOneOrNullResult();
        } finally {
            $this->debugLogger->logEnd(__CLASS__, __FUNCTION__, $__debugReturn);
        }
    }

    public function getErrorsSinceLastNotification(): array
    {
        $this->debugLogger->logStart(__CLASS__, __FUNCTION__, []);
        $__debugReturn = null;
        try {
            $qb = $this->createQueryBuilder('j');
            $qb
                ->select('log.begin, log.message, document.id')
                ->join('j.log', 'log')
                ->join('log.document', 'document')
                ->andWhere('document.deleted = 0')
                ->andWhere('document.globalStatus = :error')
                ->andWhere('document.type = :type')
                ->setParameter('error', 'Error')
                ->setParameter('type', 'E')
                ->setParameter('now', new DateTime())
                ->orderBy('log.begin', 'DESC')
                ->setMaxResults(100);

            return $__debugReturn = $qb->getQuery()->getResult();
        } finally {
            $this->debugLogger->logEnd(__CLASS__, __FUNCTION__, $__debugReturn);
        }
    }

    public function listJobDetail()
    {
        $this->debugLogger->logStart(__CLASS__, __FUNCTION__, []);
        $__debugReturn = null;
        try {
            $qb = $this->createQueryBuilder('j');
            $qb
                ->select('j.id, j.begin, j.end, j.status, j.message')
                ->addSelect('TIMESTAMPDIFF(SECOND, j.begin, j.end) as duration')
                ->orderBy('j.begin', 'DESC')
                ->setMaxResults(HomeManager::nbHistoricJobs);

            return $__debugReturn = $qb->getQuery()->getResult();
        } finally {
            $this->debugLogger->logEnd(__CLASS__, __FUNCTION__, $__debugReturn);
        }
    }

    public function findJobsToRemoveByLimitDate(DateTime $limitDate)
    {
        $this->debugLogger->logStart(__CLASS__, __FUNCTION__, ['limitDate' => $limitDate]);
        $__debugReturn = null;
        try {
            return $__debugReturn = $this->createQueryBuilder('j')
                ->select('j')
                ->where('j.status = :status')
                ->andWhere('j.param NOT IN (:params)')
                ->andWhere('j.end < :limitDate')
                ->andWhere('j.open = 0')
                ->andWhere('j.close = 0')
                ->andWhere('j.cancel = 0')
                ->andWhere('j.error = 0')
                ->setParameter('status', 'End')
                ->setParameter('params', ['cleardata', 'notification'])
                ->setParameter('limitDate', $limitDate)
                ->getQuery()
                ->getResult();
        } finally {
            $this->debugLogger->logEnd(__CLASS__, __FUNCTION__, $__debugReturn);
        }
    }

    public function findJobsForPagination(int $limit = null)
    {
        $this->debugLogger->logStart(__CLASS__, __FUNCTION__, ['limit' => $limit]);
        $__debugReturn = null;
        try {
            $queryBuilder = $this->createQueryBuilder('j')
                ->orderBy('j.status', 'DESC')
                ->addOrderBy('j.begin', 'DESC');

            if ($limit !== null) {
                $queryBuilder->setMaxResults($limit);
            }

            return $__debugReturn = $queryBuilder;
        } finally {
            $this->debugLogger->logEnd(__CLASS__, __FUNCTION__, $__debugReturn);
        }
    }

    public function findJobsFiltered(array $filters, int $limit = null)
    {
        $this->debugLogger->logStart(__CLASS__, __FUNCTION__, ['filters' => $filters, 'limit' => $limit]);
        $__debugReturn = null;
        try {
            $qb = $this->createQueryBuilder('j')
                ->orderBy('j.status', 'DESC')
                ->addOrderBy('j.begin', 'DESC');

            if (!empty($filters['param'])) {
                $paramValues = array_filter(array_map('trim', explode(',', $filters['param'])));
                if (count($paramValues) === 1) {
                    $qb->andWhere('j.param LIKE :param')
                       ->setParameter('param', '%' . $paramValues[0] . '%');
                } elseif (count($paramValues) > 1) {
                    $orParts = [];
                    foreach ($paramValues as $i => $pv) {
                        $paramName = 'param_' . $i;
                        $orParts[] = 'j.param LIKE :' . $paramName;
                        $qb->setParameter($paramName, '%' . $pv . '%');
                    }
                    $qb->andWhere('(' . implode(' OR ', $orParts) . ')');
                }
            }

            if (!empty($filters['status'])) {
                $qb->andWhere('j.status = :status')
                   ->setParameter('status', $filters['status']);
            }

            if (!empty($filters['begin_date'])) {
                $dayStart = new DateTime($filters['begin_date']);
                $dayStart->setTime(0, 0, 0);
                $dayEnd = clone $dayStart;
                $dayEnd->setTime(23, 59, 59);
                $qb->andWhere('j.begin >= :begin_start AND j.begin <= :begin_end')
                   ->setParameter('begin_start', $dayStart)
                   ->setParameter('begin_end', $dayEnd);
            }

            if (!empty($filters['end_date'])) {
                $dayStart = new DateTime($filters['end_date']);
                $dayStart->setTime(0, 0, 0);
                $dayEnd = clone $dayStart;
                $dayEnd->setTime(23, 59, 59);
                $qb->andWhere('j.end >= :end_start AND j.end <= :end_end')
                   ->setParameter('end_start', $dayStart)
                   ->setParameter('end_end', $dayEnd);
            }

            if ($limit !== null) {
                $qb->setMaxResults($limit);
            }

            return $__debugReturn = $qb;
        } finally {
            $this->debugLogger->logEnd(__CLASS__, __FUNCTION__, $__debugReturn);
        }
    }

    public function getFilterOptions(int $limit = null): array
    {
        $this->debugLogger->logStart(__CLASS__, __FUNCTION__, ['limit' => $limit]);
        $__debugReturn = null;
        try {
            $ids = null;
            if ($limit !== null) {
                $subQb = $this->createQueryBuilder('sub')
                    ->select('sub.id')
                    ->orderBy('sub.status', 'DESC')
                    ->addOrderBy('sub.begin', 'DESC')
                    ->setMaxResults($limit);
                $subResult = $subQb->getQuery()->getResult();
                $ids = array_column($subResult, 'id');

                if (empty($ids)) {
                    return $__debugReturn = [
                        'params' => [],
                        'statuses' => [],
                    ];
                }
            }

            $paramQb = $this->createQueryBuilder('j2')
                ->select('DISTINCT j2.param')
                ->orderBy('j2.param', 'ASC');
            if ($ids !== null) {
                $paramQb->andWhere('j2.id IN (:ids)')->setParameter('ids', $ids);
            }
            $rawParams = array_column($paramQb->getQuery()->getResult(), 'param');

            return $__debugReturn = [
                'params' => $rawParams,
                'statuses' => ['Start', 'End'],
            ];
        } finally {
            $this->debugLogger->logEnd(__CLASS__, __FUNCTION__, $__debugReturn);
        }
    }
}

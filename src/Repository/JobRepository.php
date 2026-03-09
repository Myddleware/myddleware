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
use App\Entity\Rule;
use App\Manager\HomeManager;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Persistence\ManagerRegistry;

class JobRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Job::class);
    }

    /**
     * @throws NonUniqueResultException
     */
    public function findJobStarted($begin): ?Job
    {
        return $this->createQueryBuilder('j')
            ->select('j')
            ->where('j.status = :status')
            ->andWhere('j.begin < :timeLimit')
            ->setParameter('status', 'Start')
            ->setParameter('timeLimit', $begin)
            ->getQuery()
            ->setMaxResults(1)
            ->getOneOrNullResult();
    }

    /**
     * @return Job[]
     */
    public function getErrorsSinceLastNotification(): array
    {
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

        return $qb->getQuery()->getResult();
    }

    public function listJobDetail()
    {
        $qb = $this->createQueryBuilder('j');
        $qb
            ->select('j.id, j.begin, j.end, j.status, j.message')
            ->addSelect('TIMESTAMPDIFF(SECOND, j.begin, j.end) as duration')
            ->orderBy('j.begin', 'DESC')
            ->setMaxResults(HomeManager::nbHistoricJobs);

        return $qb->getQuery()->getResult();
    }

    public function findJobsToRemoveByLimitDate(DateTime $limitDate)
    {
        return $this->createQueryBuilder('j')
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
    }

    /**
     * Optimized query for task list pagination to prevent timeouts
     * Uses search_limit config and proper indexing strategy
     *
     * Recommended DB index for optimal performance:
     * CREATE INDEX idx_job_status_begin ON job (status DESC, begin DESC);
     * CREATE INDEX idx_job_begin_status ON job (begin DESC, status DESC);
     */
    public function findJobsForPagination(int $limit = null)
    {
        $queryBuilder = $this->createQueryBuilder('j')
            ->orderBy('j.status', 'DESC')
            ->addOrderBy('j.begin', 'DESC');

        if ($limit !== null) {
            $queryBuilder->setMaxResults($limit);
        }

        return $queryBuilder;
    }

    public function findJobsFiltered(array $filters, int $limit = null)
    {
        $qb = $this->createQueryBuilder('j')
            ->orderBy('j.status', 'DESC')
            ->addOrderBy('j.begin', 'DESC');

        if (!empty($filters['param'])) {
            $qb->andWhere('j.param LIKE :param')
               ->setParameter('param', '%' . $filters['param'] . '%');
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

        return $qb;
    }

    public function getFilterOptions(int $limit = null): array
    {
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
                return [
                    'rules' => [],
                    'statuses' => [],
                ];
            }
        }

        // Extract distinct param values, then find rule IDs within them
        $paramQb = $this->createQueryBuilder('j2')
            ->select('DISTINCT j2.param');
        if ($ids !== null) {
            $paramQb->andWhere('j2.id IN (:ids)')->setParameter('ids', $ids);
        }
        $rawParams = array_column($paramQb->getQuery()->getResult(), 'param');

        // Extract rule IDs (hex strings of 13+ chars) from param values
        $ruleIdSet = [];
        foreach ($rawParams as $paramValue) {
            if (preg_match_all('/\b([0-9a-f]{13,})\b/i', $paramValue, $matches)) {
                foreach ($matches[1] as $match) {
                    $ruleIdSet[$match] = true;
                }
            }
        }
        $ruleIds = array_keys($ruleIdSet);

        // Look up rule names for those IDs
        $ruleMap = [];
        if (!empty($ruleIds)) {
            $ruleRepo = $this->getEntityManager()->getRepository(Rule::class);
            $rules = $ruleRepo->createQueryBuilder('r')
                ->select('r.id, r.name')
                ->where('r.id IN (:ids)')
                ->setParameter('ids', $ruleIds)
                ->orderBy('r.name', 'ASC')
                ->getQuery()
                ->getResult();
            foreach ($rules as $rule) {
                $ruleMap[$rule['id']] = $rule['name'];
            }
        }

        return [
            'rules' => $ruleMap,
            'statuses' => ['Start', 'End'],
        ];
    }
}

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
            ->getOneOrNullResult();
    }

    /**
     * @return Job[]
     */
    public function getErrorsSinceLastNotification(): array
    {
        $sub = $this->_em->createQueryBuilder();
        $sub->select('MAX(begin)');
        $sub->from('App:Job', 'job');
        $sub->andWhere('job.param = :param')
            ->setParameter('param', 'notification')
            ->andWhere('job.end >= job.begin');

        $qb = $this->createQueryBuilder('j');
        $qb
            ->select('log.begin, log.message, document.id, rule.name')
            ->join('j.log', 'log')
            ->join('log.document', 'document')
            ->andWhere('document.deleted = 0')
            ->andWhere('j.begin BETWEEN ('.$sub->getDQL().') AND :now')
            ->andWhere('document.dateModified BETWEEN ('.$sub->getDQL().' AND :now)')
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
}

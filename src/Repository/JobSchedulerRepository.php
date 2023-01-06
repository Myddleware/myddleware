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

use App\Entity\JobScheduler;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\ManagerRegistry;

class JobSchedulerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, JobScheduler::class);
    }

    /**
     * @return JobScheduler[]
     */
    public function findJobsToRun(): array
    {
        $qb = $this->createQueryBuilder('j');

        $qb
            ->select('j')
            ->addSelect('(CASE 
                 WHEN rule_order.order IS NOT NULL
                 THEN rule_order.order
                 ELSE j.jobOrder
            END) AS HIDDEN jobOrder')
            ->where($qb->expr()->orX(
                'j.lastRun IS NULL',
                $qb->expr()->andX(
                    'j.lastRun IS NOT NULL',
                    'TIMESTAMPDIFF(MINUTE,j.lastRun,UTC_TIMESTAMP()) >= j.period'
                )
            ))
            ->andWhere('j.active = 1')
            ->leftJoin('App\Entity\RuleOrder', 'rule_order', Join::WITH, 'j.paramValue1 = rule_order.rule AND j.command = :command')
            ->setParameter('command', 'synchro');

        return $qb->getQuery()->getResult();
    }
}

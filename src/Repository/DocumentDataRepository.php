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

use App\Entity\DocumentData;
use App\Entity\Rule;
use App\Service\DebugLogger;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class DocumentDataRepository extends ServiceEntityRepository
{
    private DebugLogger $debugLogger;

    public function __construct(ManagerRegistry $registry, DebugLogger $debugLogger)
    {
        parent::__construct($registry, DocumentData::class);
        $this->debugLogger = $debugLogger;
    }

    public function findDataToRemoveByRule(Rule $rule, DateTime $limitDate)
    {
        $this->debugLogger->logStart(__CLASS__, __FUNCTION__, ['rule' => $rule, 'limitDate' => $limitDate]);
        $__debugReturn = null;
        try {
            return $__debugReturn = $this->createQueryBuilder('dd')
                ->select('dd')
                ->join('dd.doc_id', 'document')
                ->where('document.rule = :rule')
                ->andWhere('document.deleted = 0')
                ->andWhere('document.date_modified <= :limitDate')
                ->setParameter('rule', $rule)
                ->setParameter('limitDate', $limitDate)
                ->getQuery()
                ->getResult();
        } finally {
            $this->debugLogger->logEnd(__CLASS__, __FUNCTION__, $__debugReturn);
        }
    }
}

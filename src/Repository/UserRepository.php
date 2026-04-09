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

use App\Entity\User;
use App\Service\DebugLogger;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class UserRepository extends ServiceEntityRepository
{
    private DebugLogger $debugLogger;

    public function __construct(ManagerRegistry $registry, DebugLogger $debugLogger)
    {
        parent::__construct($registry, User::class);
        $this->debugLogger = $debugLogger;
    }

    public function loadUserByUsername(string $username)
    {
        $this->debugLogger->logStart(__CLASS__, __FUNCTION__, ['username' => $username]);
        $__debugReturn = null;
        try {
            return $__debugReturn = $this->createQueryBuilder('u')
                ->where('u.username = :username OR u.email = :email')
                ->setParameter('username', $username)
                ->setParameter('email', $username)
                ->getQuery()
                ->getOneOrNullResult();
        } finally {
            $this->debugLogger->logEnd(__CLASS__, __FUNCTION__, $__debugReturn);
        }
    }

    public function findEmailsToNotification()
    {
        $this->debugLogger->logStart(__CLASS__, __FUNCTION__);
        $__debugReturn = null;
        try {
            return $__debugReturn = $this->createQueryBuilder('u')
                ->select('u.email')
                ->where('u.roles LIKE :role')
                ->andWhere('u.enabled = 1')
                ->setParameter('role', '%ADMIN%')
                ->getQuery()
                ->getResult();
        } finally {
            $this->debugLogger->logEnd(__CLASS__, __FUNCTION__, $__debugReturn);
        }
    }
}

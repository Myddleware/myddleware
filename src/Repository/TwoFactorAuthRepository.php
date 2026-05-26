<?php
/*********************************************************************************
 * This file is part of Myddleware.
 * @package Myddleware
 * @copyright Copyright (C) 2013 - 2015  Stéphane Faure - CRMconsult EURL
 * @copyright Copyright (C) 2015 - 2016  Stéphane Faure - Myddleware ltd - contact@myddleware.com
 * @link http://www.myddleware.com
 *
 * This file is part of Myddleware.
 *
 * Myddleware is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Myddleware is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Myddleware.  If not, see <http://www.gnu.org/licenses/>.
 *********************************************************************************/

namespace App\Repository;

use App\Entity\TwoFactorAuth;
use App\Entity\User;
use App\Service\DebugLogger;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method TwoFactorAuth|null find($id, $lockMode = null, $lockVersion = null)
 * @method TwoFactorAuth|null findOneBy(array $criteria, array $orderBy = null)
 * @method TwoFactorAuth[]    findAll()
 * @method TwoFactorAuth[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TwoFactorAuthRepository extends ServiceEntityRepository
{
    private DebugLogger $debugLogger;

    public function __construct(ManagerRegistry $registry, DebugLogger $debugLogger)
    {
        parent::__construct($registry, TwoFactorAuth::class);
        $this->debugLogger = $debugLogger;
    }

    public function findByUser(User $user): ?TwoFactorAuth
    {
        $this->debugLogger->logStart(__CLASS__, __FUNCTION__, ['user' => $user->getId()]);
        $__debugReturn = null;
        try {
            return $__debugReturn = $this->findOneBy(['user' => $user]);
        } finally {
            $this->debugLogger->logEnd(__CLASS__, __FUNCTION__, $__debugReturn);
        }
    }

    public function findByRememberToken(string $token): ?TwoFactorAuth
    {
        $this->debugLogger->logStart(__CLASS__, __FUNCTION__, ['token' => $token]);
        $__debugReturn = null;
        try {
            return $__debugReturn = $this->findOneBy(['rememberToken' => $token]);
        } finally {
            $this->debugLogger->logEnd(__CLASS__, __FUNCTION__, $__debugReturn);
        }
    }
}
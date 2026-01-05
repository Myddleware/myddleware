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
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class SolutionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Solution::class);
    }

    // Liste des solutions actives
    public function solutionActive()
    {
        return $this
          ->createQueryBuilder('s')
          ->select('s')
          ->where('s.active = :active')
          ->setParameter('active', 1)
          ->getQuery()
          ->getResult();
    }

    // Liste des solutions en fonction des connecteurs
    public function solutionConnector($type, $is_support, $id)
    {
        $qb = $this->createQueryBuilder('s');

        $field = (('target' == $type) ? 'target' : 'source');

        $qb->select('s', 'c')
         ->innerJoin('s.connector', 'c');

        // si ce n'est pas le support alors on affecte l'id client sinon on affiche tout
        // On affiche uniquement les connecteurs du user
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

        return $qb->getQuery()
                  ->getResult();
    }

    // Liste des solutions en fonction des types
    public function solutionConnectorType($type)
    {
        $qb = $this->createQueryBuilder('s');

        $field = (('target' == $type) ? 'target' : 'source');

        $qb->select('s.name')
         ->where('s.active = :active AND s.'.$field.' = :type')
         ->setParameter('active', 1)
         ->setParameter('type', 1)
         ->groupBy('s.name')
         ->orderBy('s.name', 'ASC');

        return $qb->getQuery()
                  ->getResult();
    }

    public function resolveName($val): ?string
    {
        if (!$val) return null;
        if (!is_numeric($val)) return (string) $val;
        $solution = $this->find((int) $val); 
        if ($solution && method_exists($solution, 'getName')) {
            return (string) $solution->getName();
        }
        
        return null;
    }
}

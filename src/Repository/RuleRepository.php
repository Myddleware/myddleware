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
use App\Entity\User;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @method Rule|null find($id, $lockMode = null, $lockVersion = null)
 * @method Rule|null findOneBy(array $criteria, array $orderBy = null)
 * @method Rule[]    findAll()
 * @method Rule[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RuleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Rule::class);
    }

    /**
     * @throws NonUniqueResultException
     * @throws \Doctrine\ORM\NoResultException
     */
    public function findCountAllRuleByUser($id)
    {
        return $this->createQueryBuilder('r')
                    ->select('COUNT(r)')
                    ->where('r.createdBy = :user_id')
                    ->setParameter('user_id', $id)
                    ->getQuery()
                    ->getSingleScalarResult();
    }

    // Retourne toutes les règles d'un user
    public function findListRuleByUser(User $user, $ruleName = null): Query
    {
        $sql = $this->createQueryBuilder('r')
            ->join('r.connectorSource', 'cs')
            ->join('r.connectorTarget', 'ct')
            ->join('cs.solution', 'Solution_source')
            ->join('ct.solution', 'Solution_target')
            ->addSelect('r.id')
            ->addSelect('r.dateCreated')
            ->addSelect('r.name')
            ->addSelect('r.active')
            ->addSelect('r.nameSlug')
            ->addSelect('cs.name lbl_source')
            ->addSelect('ct.name lbl_target')
            ->addSelect('Solution_source.name solution_source')
            ->addSelect('Solution_target.name solution_target')
            ;

        // si ce n'est pas le support alors on affecte l'id client sinon on affiche tout
        if (!$user->isAdmin()) {
            $sql->where('r.createdBy = :user_id AND r.deleted = 0')
                 ->setParameter('user_id', $user->getId());
        } else {
            $sql->where('r.deleted = 0');
        }

        // Add search condition
        if ($ruleName) {
            $sql->andWhere('r.name LIKE :name')
            ->setParameter('name', '%' . $ruleName . '%');
        }

        return $sql->getQuery();
    }

    // Infos connecteurs et solution d'une règle
    public function infosConnectorByRule($id)
    {
        return $this->createQueryBuilder('r')
            ->innerJoin('r.connectorSource', 'cs')
            ->innerJoin('r.connectorTarget', 'ct')
            ->innerJoin('cs.solution', 'Solution_source')
            ->innerJoin('ct.solution', 'Solution_target')
            ->addSelect('cs.name lbl_source')
            ->addSelect('ct.name lbl_target')
            ->addSelect('cs.id id_source')
            ->addSelect('ct.id id_target')
            ->addSelect('Solution_source.name solution_source')
            ->addSelect('Solution_target.name solution_target')
            ->where('r.id = :rule_id')
            ->setParameter('rule_id', $id)
            ->getQuery()
            ->getResult();
    }

    public function findActiveRules()
    {
        return $this->createQueryBuilder('r')
            ->select('r.id, r.name')
            ->where('r.active = 1 ')
            ->andWhere('r.deleted = 0 ')
            ->getQuery()
            ->getResult();
    }

    /**
     * @throws NonUniqueResultException
     */
    public function findByRuleParam($ruleId): ?Rule
    {
        return $this->createQueryBuilder('r')
            ->select('r')
            ->leftJoin('App\Entity\RuleParam', 'rule_param', Join::WITH, 'r.id = rule_param.rule AND rule_param.name = :mode')
            ->where('r.id = :rule_id')
            ->setParameter('name', 'mode')
            ->setParameter('rule_id', $ruleId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return Rule[]
     */
    public function errorByRule(User $user = null): array
    {
        $qb = $this->createQueryBuilder('r')
            ->select('r.name, r.id, COUNT(document.id) as cpt')
            ->join('r.documents', 'document')
            ->andWhere('document.globalStatus IN (:status)')
            ->andWhere('document.deleted = 0')
            ->setParameter('status', ['Error'])
            ->groupBy('r.id')
            ->having('cpt > 0')
            ->orderBy('cpt', 'DESC');

        if ($user && !$user->isAdmin()) {
            $qb->andWhere('r.createdBy = :user')
                ->setParameter('user', $user);
        }

        return $qb->getQuery()->getResult();
    }

    public function findRulesByIds(array $rulesIds)
    {
        return $this->createQueryBuilder('r')
            ->select('r')
            ->where('r.id IN (:ids)')
            ->setParameter('ids', $rulesIds)
            ->getQuery()
            ->getResult();
    }

    public function findAllRulesByOrder()
    {
        return $this->createQueryBuilder('r')
            ->select('r')
            ->join('r.orders', 'rule_order')
            ->where('r.active = 1')
            ->andWhere('r.deleted = 0')
            ->orderBy('rule_order.order', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Rule[]
     */
    public function findRulesWithDeletedParams(): array
    {
        return $this->createQueryBuilder('r')
            ->select('r')
            ->join('r.params', 'rule_param')
            ->where('rule_param.name = :deleted')
            ->setParameter('deleted', 'deleted')
            ->getQuery()
            ->getResult();
    }

    public function getSolutionsByJob(Job $job)
    {
        return $this->createQueryBuilder('r')
            ->select('connector_source.id as sol_id_source, connector_target.id as sol_id_target')
            ->join('r.logs', 'log')
            ->join('r.connectorSource', 'connector_source')
            ->join('r.connectorTarget', 'connector_target')
            ->where('log.job = :job')
            ->setParameter('job', $job)
            ->getQuery()
            ->getResult();
    }

    public function getRuleToOrder()
    {
        return $this->createQueryBuilder('rule')
            ->select('rule.id, GROUP_CONCAT(relationship.fieldId SEPARATOR \';\')')
            ->join('rule.relationsShip', 'relationship')
            ->where('rule.deleted = 0')
            ->groupBy('rule.id')
            ->getQuery()
            ->getResult();
    }


    public static function findActiveRulesNames(EntityManagerInterface $entityManager, bool $isDocSearchResult=false)
    {
        $qb = $entityManager->createQueryBuilder();

        $qb->select('r.name')
        ->from('App\Entity\Rule', 'r')
        ->where('r.deleted = 0');

        $results = $qb->getQuery()->getScalarResult();

        $curatedResults =  array_column($results, 'name');
        sort($curatedResults);
        $finalResults = array_flip($curatedResults);
        if ($isDocSearchResult) {
            $finalResults = array_flip($finalResults);
        }
        return $finalResults;
    }

    public static function findActiveRulesNamesOrdered($entityManager)
    {
        $rules = $entityManager->getRepository(Rule::class)->findBy(['active' => true]);

        $rulesNames = [];
        foreach ($rules as $rule) {
            $rulesNames[$rule->getName()] = $rule->getId();
        }

        return $rulesNames;
    }

    public static function findActiveRulesIds(EntityManagerInterface $entityManager)
    {
        $qb = $entityManager->createQueryBuilder();

        $qb->select('r.id')
        ->from('App\Entity\Rule', 'r')
        ->where('r.deleted = 0');

        $results = $qb->getQuery()->getScalarResult();

        $curatedResults =  array_column($results, 'id');
        return $curatedResults;
    }

    public static function findModuleSource(EntityManagerInterface $entityManager)
    {
        $qb = $entityManager->createQueryBuilder();

        $qb->select('r.moduleSource')
        ->from('App\Entity\Rule', 'r')
        ->where('r.deleted = 0');

        $results = $qb->getQuery()->getScalarResult();

        $curatedResults =  array_column($results, 'moduleSource');
        $finalResults = array_flip($curatedResults);
        return $finalResults;
    }

    public static function findModuleTarget(EntityManagerInterface $entityManager)
    {
        $qb = $entityManager->createQueryBuilder();

        $qb->select('r.moduleTarget')
        ->from('App\Entity\Rule', 'r')
        ->where('r.deleted = 0');

        $results = $qb->getQuery()->getScalarResult();

        $curatedResults =  array_column($results, 'moduleTarget');
        $finalResults = array_flip($curatedResults);
        return $finalResults;
    }

    public static function findNameSlug(EntityManagerInterface $entityManager)
    {
        $qb = $entityManager->createQueryBuilder();

        $qb->select('r.nameSlug')
        ->from('App\Entity\Rule', 'r')
        ->where('r.deleted = 0');

        $results = $qb->getQuery()->getScalarResult();

        $curatedResults =  array_column($results, 'nameSlug');
        $finalResults = array_flip($curatedResults);
        return $finalResults;
    }

    // Remove lock from rule using a job id
	public function removeLock($jobId) {
        $empty = null;
		$qr = $this->createQueryBuilder('r')
			->update()
			->set('r.readJobLock', ':empty')
			->where('r.readJobLock = :readJobLock')
			->setParameter('readJobLock', $jobId)
            ->setParameter('empty', $empty)
			->getQuery();
        $qr->execute();
	}

}

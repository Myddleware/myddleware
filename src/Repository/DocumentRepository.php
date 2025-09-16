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

use App\Entity\Document;
use App\Entity\Job;
use App\Entity\Rule;
use App\Entity\User;
use App\Manager\HomeManager;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\ManagerRegistry;

class DocumentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Document::class);
    }

    /**
     * @return Document[]
     *
     * @param mixed $limit
     * @param mixed $attempt
     */
    public function findDocumentsError($limit, $attempt): array
    {
        $qb = $this->createQueryBuilder('d');
        $qb
            ->select('d')
            ->leftJoin('App\Entity\RuleOrder', 'rule_order', Join::WITH, 'd.rule = rule_order.rule')
            ->andWhere('d.globalStatus = :globalStatus')
            ->andWhere('d.deleted = 0')
            ->andWhere('d.attempt <= :attempt')
            ->setParameter('globalStatus', 'Error')
            ->setParameter('attempt', $attempt)
            ->orderBy('rule_order.order', 'ASC')
            ->addOrderBy('d.sourceDateModified', 'ASC')
            ->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }

    /**
     * @throws NonUniqueResultException
     */
    public function findDocumentByFilters(array $filters = []): ?Document
    {
        $qb = $this->createQueryBuilder('d');
        $qb->select('d')
            ->andWhere('d.deleted = 0');

        if (!empty($filters['rule'])) {
            $qb->andWhere('d.rule = :rule')
                ->setParameter('rule', $filters['rule']);
        }

        if (!empty($filters['source'])) {
            $qb->andWhere('d.source = :source')
                ->setParameter('source', $filters['source']);
        }

        if (!empty($filters['globalStatus'])) {
            $qb->andWhere('d.globalStatus IN (:globalStatus)')
                ->setParameter('status', $filters['globalStatus']);
        }

        if (!empty($filters['dateCreated'])) {
            $qb->andWhere('d.dateCreated < :dateCreated')
                ->setParameter('dateCreated', $filters['dateCreated']);
        }

        return $qb->getQuery()->setMaxResults(1)->getOneOrNullResult();
    }

    /**
     * @throws NonUniqueResultException
     */
    public function findDocumentByReadyToSend(array $filters = []): ?Document
    {
        $qb = $this->createQueryBuilder('d');
        $qb->select('d')
            ->andWhere('d.deleted = 0')
            ->andWhere(
                $qb->expr()->orX(
                    'd.globalStatus = :error',
                    $qb->expr()->andX(
                        'd.globalStatus = :open',
                        'd.status <> :ready_to_send'
                    )
                )
            )
            ->setParameter('ready_to_send', 'Ready_to_send')
            ->setParameter('open', 'Open')
            ->setParameter('error', 'Error');

        if (!empty($filters['rule'])) {
            $qb->andWhere('d.rule = :rule')
                ->setParameter('rule', $filters['rule']);
        }

        if (!empty($filters['source'])) {
            $qb->andWhere('d.source = :source')
                ->setParameter('source', $filters['source']);
        }

        if (!empty($filters['dateCreated'])) {
            $qb->andWhere('d.dateCreated < :dateCreated')
                ->setParameter('dateCreated', $filters['dateCreated']);
        }

        return $qb->getQuery()->setMaxResults(1)->getOneOrNullResult();
    }

    public function getErrorList()
    {
        return $this->createQueryBuilder('d')
            ->select('rule.name, d.id d.dateModified as date_modified')
            ->join('d.rule', 'rule')
            ->andWhere('d.globalStatus = :error')
            ->andWhere('d.deleted = 0')
            ->andWhere('rule.active = 1')
            ->andWhere('rule.deleted = 0')
            ->setParameter('error', 'Error')
            ->getQuery()
            ->getResult();
    }

    public function countTypeDoc(User $user = null)
    {
        $qb = $this->createQueryBuilder('d')
            ->select('COUNT(d) as nb, d.globalStatus as global_status')
            ->andWhere('d.deleted = 0')
            ->groupBy('d.globalStatus')
            ->orderBy('nb', 'DESC');

        if ($user && !$user->isAdmin()) {
            $qb->andWhere('d.createdBy = :user')
                ->setParameter('user', $user);
        }

        return $qb->getQuery()->getResult();
    }

    public function countTransferHisto(User $user = null)
    {
        $qb = $this->createQueryBuilder('d')
                ->select("DATE_FORMAT(d.dateModified, '%Y-%m-%d') AS date")
                ->addSelect('d.globalStatus')
                ->addSelect('COUNT(d.id) AS nb')
                ->andWhere('d.deleted = 0')
                ->andWhere('d.dateModified >= :days ')
                ->setParameter('days', new DateTime('-'.HomeManager::nbHistoricJobs.' day'))
                ->groupBy('date')
                ->addGroupBy('d.globalStatus');

        if ($user && !$user->isAdmin()) {
            $qb->andWhere('d.createdBy = :user')
                    ->setParameter('user', $user);
        }

        return $qb->getQuery()->getResult();
    }

    public function getSolutionsByJob(Job $job)
    {
        return $this->createQueryBuilder('d')
            ->select('d')
            ->join('d.logs', 'log')
            ->where('log.job = :job')
            ->setParameter('job', $job)
            ->getQuery()
            ->getResult(AbstractQuery::HYDRATE_ARRAY);
    }

    public function getDocumentsForMassAction($action, $dataType, $ids, $forceAll, $fromStatus)
    {
        $qb = $this->createQueryBuilder('d')
            ->select('d')
            ->andWhere('d.deleted = :deleted')
            ->join('d.rule', 'rule')
            ->setParameter('deleted', 'restore' == $action)
            ->orderBy('d.rule', 'ASC');

        if ('rule' == $dataType) {
            $qb
                ->andWhere('rule.id IN (:ids)')
                ->setParameter('ids', $ids);
        } elseif ('document' === $dataType) {
            $qb
                ->andWhere('d.id IN (:ids)')
                ->setParameter('ids', $ids);
        }

        if (
            'Y' != $forceAll
            && 'restore' != $action
            && 'changeStatus' != $action
        ) {
            $qb->andWhere('Document.globalStatus IN (:globalStatus)')
                ->setParameter('globalStatus', ['Open', 'Error']);
        }

        if ('changeStatus' == $action) {
            $qb->andWhere('d.status = :status')
            ->setParameter('status', $fromStatus);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @throws NonUniqueResultException
     */
    public function findDocumentBySourceOrTarget(Rule $rule, Document $document, int $id, string $type = 'source'): ?Document
    {
        $qb = $this->createQueryBuilder('d');
        $qb
            ->select('d')
            ->andWhere('d.rule = :rule')
            ->andWhere('d.deleted = 0')
            ->andWhere('d.id <> :document_id')
            ->andWhere($qb->expr()->orX(
                $qb->expr()->orX(
                    'd.globalStatus = :close',
                    $qb->expr()->andX(
                        'd.globalStatus = :cancel',
                        'd.status = :no_send'
                    )
                )
            ))
            ->setParameter('no_send', 'No_send')
            ->setParameter('cancel', 'Cancel')
            ->setParameter('close', 'Close')
            ->setParameter('document_id', $document->getId())
            ->setParameter('id', $id)
            ->setParameter('rule', $rule);

        if ('target' === $type) {
            $qb->andWhere('d.target = :id');
        } else {
            $qb->andWhere('d.source = :id');
        }

        return $qb->getQuery()->setMaxResults(1)->getOneOrNullResult();
    }

    public function getFluxPagination(array $data)
    {
        $qb = $this->createQueryBuilder('document');
        $qb
            ->select('document.id, document.dateCreated, document.dateModified as date_modified, document.status, document.source as source_id, document.target as target_id, document.sourceDateModified as source_date_modified, document.mode, document.type, document.attempt, document.globalStatus as global_status')
            ->addSelect('user.username, rule.name as rule_name, rule.id as rule_id')
            ->join('document.rule', 'rule')
            ->join('document.createdBy', 'user')
            ->andWhere('document.deleted = 0');

        // No search if no parameter set
        if (empty($data)) {
            $qb->andWhere('document.deleted < 0');
        }

        if (!empty($data['source_content']) && is_string($data['source_content'])) {
            $qb->innerJoin('document.datas', 'document_data_source')
                ->andWhere('document_data_source.data LIKE :source_content')
                ->andWhere('document_data_source.type = :document_data_type')
                ->setParameter('source_content', '%'.$data['source_content'].'%')
                ->setParameter('document_data_type', 'S');
        }

        if (!empty($data['target_content']) && is_string($data['target_content'])) {
            $qb->innerJoin('document.datas', 'document_data_target')
                ->andWhere('document_data_target.data LIKE :target_content')
                ->andWhere('document_data_target.type = :document_data_type')
                ->setParameter('target_content', '%'.$data['target_content'].'%')
                ->setParameter('document_data_type', 'T');
        }

        if (!empty($data['date_modif_start']) && is_string($data['date_modif_start'])) {
            $qb
                ->andWhere('document.dateModified >= :dateModified')
                ->setParameter('dateModified', $data['date_modif_start']);
        }

        if (!empty($data['date_modif_end']) && is_string($data['date_modif_end'])) {
            $qb
                ->andWhere('document.dateModified <= :dateModifiedEnd')
                ->setParameter('dateModifiedEnd', $data['date_modif_end']);
        }

        if (
                (!empty($data['rule']) && is_string($data['rule']))
            or (!empty($data['customWhere']['rule']) && is_string($data['customWhere']['rule']))
        ) {
            $ruleFilter = (!empty($data['customWhere']['rule']) ? $data['customWhere']['rule'] : $data['rule']);
            $qb
                ->andWhere('rule.name = :rule_name')
                ->setParameter('rule_name', trim($ruleFilter));
        }

        if (!empty($data['status'])) {
            $qb
                ->andWhere('document.status = :status')
                ->setParameter('status', $data['status']);
        }

        // customWhere can have several status (open and error from the error dashlet in the home page)
        if (!empty($data['customWhere']['gblstatus'])) {
            $i = 1;
            $orModule = $qb->expr()->orx();
            foreach ($data['customWhere']['gblstatus'] as $gblstatus) {
                $orModule->add($qb->expr()->eq('document.globalStatus', ':gblstatus'.$i));
                $qb->setParameter('gblstatus'.$i, $gblstatus);
                ++$i;
            }
            $qb->andWhere($orModule);
        } elseif (!empty($data['gblstatus'])) {
            $qb
                ->andWhere('document.globalStatus = :globalStatus')
                ->setParameter('globalStatus', $data['gblstatus']);
        }

        if (!empty($data['type'])) {
            $qb
                ->andWhere('document.type = :type')
                ->setParameter('type', $data['type']);
        }

        if (!empty($data['target_id'])) {
            $qb
                ->andWhere('document.target LIKE :target')
                ->setParameter('target', '%'.$data['target_id'].'%');
        }

        if (!empty($data['source_id'])) {
            $qb
                ->andWhere('document.source LIKE :source')
                ->setParameter('source', '%'.$data['source_id'].'%');
        }
        if (!empty($data['user']) && !$data['user']->isAdmin()) {
            $qb
                ->andWhere('document.createdBy = :createdBy')
                ->setParameter('createdBy', $data['user']);
        }
        if (!empty($data['limit'])) {
            $qb->setMaxResults($data['limit']);
        }
        $qb->orderBy('document.dateModified', 'DESC');

        return $qb->getQuery()->getResult(AbstractQuery::HYDRATE_ARRAY);
    }

    public static function findDocType(EntityManagerInterface $entityManager)
    {
        $qb = $entityManager->createQueryBuilder();

        
        $qb->select('d.type')
        ->from('App\Entity\Document', 'd')
        ->where('d.deleted = 0')
        ->groupBy('d.type');

        $results = $qb->getQuery()->getResult(AbstractQuery::HYDRATE_ARRAY);
        $curatedResults =  array_column($results, 'type');
        $final = array_flip($curatedResults);
        //$finale = array_flip($test);

        return $final;
    }
    
    public static function findStatusType(EntityManagerInterface $entityManager)
    {
        $qb = $entityManager->createQueryBuilder();

        $qb->select('d.status')
        ->from('App\Entity\Document', 'd')
        ->where('d.deleted = 0');

        $results = $qb->getQuery()->getScalarResult();

        $curatedResults =  array_column($results, 'status');
        $finalResults = array_flip($curatedResults);
        return $finalResults;
    }
	
    // Remove lock from document using a job id
	public function removeLock($jobId) {
		$q = $this->createQueryBuilder('d')
			->update()
			->set('d.jobLock', ':empty')
			->where('d.jobLock = :jobLock')
			->setParameter('jobLock', $jobId)
            ->setParameter('empty', '')
			->getQuery();
        $q->execute();
	}

    public function countNbDocuments(): int 
    {

        return (int) $this->createQueryBuilder('d')
                ->select('COUNT(d.id)')
                ->andWhere('d.deleted = 0')
                ->andWhere('d.status = :status')
                ->andWhere('d.deleted = 0')
                ->setParameter('status', 'send')
                ->getQuery()
                ->getSingleScalarResult();
    }
}

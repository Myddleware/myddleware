<?php

namespace App\Repository;

use App\Entity\Job;
use App\Entity\Log;
use App\Entity\Rule;
use App\Service\DebugLogger;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class LogRepository extends ServiceEntityRepository
{
    private DebugLogger $debugLogger;

    public function __construct(ManagerRegistry $registry, DebugLogger $debugLogger)
    {
        parent::__construct($registry, Log::class);
        $this->debugLogger = $debugLogger;
    }

    public function findLogsToRemoveByRule(Rule $rule, DateTime $limitDate)
    {
        $this->debugLogger->logStart(__CLASS__, __FUNCTION__, ['rule' => $rule, 'limitDate' => $limitDate]);
        $__debugReturn = null;
        try {
            return $__debugReturn = $this->createQueryBuilder('l')
                ->join('l.document', 'document')
                ->andWhere('l.rule = :rule')
                ->andWhere('l.msg IN (:messages)')
                ->andWhere('document.globalStatus IN (:globalStatus)')
                ->andWhere('document.deleted = 0')
                ->andWhere('document.dateModified < :limitDate')
                ->setParameter('rule', $rule)
                ->setParameter('messages', ['Status : Filter_OK', 'Status : Predecessor_OK', 'Status : Relate_OK', 'Status : Transformed', 'Status : Ready_to_send'])
                ->setParameter('globalStatus', ['Close', 'Cancel'])
                ->setParameter('limitDate', $limitDate)
                ->getQuery()
                ->getResult()
                ;
        } finally {
            $this->debugLogger->logEnd(__CLASS__, __FUNCTION__, $__debugReturn);
        }
    }

    public function getLogsReportForDocumentsSent(Job $job)
    {
        $this->debugLogger->logStart(__CLASS__, __FUNCTION__, ['job' => $job]);
        $__debugReturn = null;
        try {
            return $__debugReturn = $this->createQueryBuilder('l')
                ->select('COUNT(DISTINCT document) as nb')
                ->addSelect('document.globalStatus')
                ->join('l.document', 'document')
                ->andWhere('l.job = :job')
                ->setParameter('job', $job)
                ->orderBy('document.globalStatus')
                ->getQuery()
                ->getResult()
                ;
        } finally {
            $this->debugLogger->logEnd(__CLASS__, __FUNCTION__, $__debugReturn);
        }
    }

    public function findNewErrorLogs($alertDateRef)
    {
        $this->debugLogger->logStart(__CLASS__, __FUNCTION__, ['alertDateRef' => $alertDateRef]);
        $__debugReturn = null;
        try {
            return $__debugReturn = $this->createQueryBuilder('l')
                ->select('l.created, l.type, l.message')
                ->where('l.created > :alertDateRef')
                ->andWhere('l.type = :type')
                ->setParameter('alertDateRef', $alertDateRef)
                ->setParameter('type', 'E')
                ->getQuery()
                ->getResult();
        } finally {
            $this->debugLogger->logEnd(__CLASS__, __FUNCTION__, $__debugReturn);
        }
    }
}

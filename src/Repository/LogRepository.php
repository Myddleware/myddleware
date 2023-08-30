<?php

namespace App\Repository;

use App\Entity\Job;
use App\Entity\Log;
use App\Entity\Rule;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Log|null find($id, $lockMode = null, $lockVersion = null)
 * @method Log|null findOneBy(array $criteria, array $orderBy = null)
 * @method Log[]    findAll()
 * @method Log[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Log::class);
    }

    // /**
    //  * @return Log[] Returns an array of Log objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('l.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Log
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
    public function findLogsToRemoveByRule(Rule $rule, DateTime $limitDate)
    {
        return $this->createQueryBuilder('l')
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
    }

    public function getLogsReportForDocumentsSent(Job $job)
    {
        return $this->createQueryBuilder('l')
            ->select('COUNT(DISTINCT document) as nb')
            ->addSelect('document.globalStatus')
            ->join('l.document', 'document')
            ->andWhere('l.job = :job')
            ->setParameter('job', $job)
            ->orderBy('document.globalStatus')
            ->getQuery()
            ->getResult()
            ;
    }

    public function findNewErrorLogs($alertDateRef)
    {
        return $this->createQueryBuilder('l')
            ->select('l.created, l.type, l.message')
            ->where('l.created > :alertDateRef')
            ->andWhere('l.type = :type')
            ->setParameter('alertDateRef', $alertDateRef)
            ->setParameter('type', 'E')
            ->getQuery()
            ->getResult();
    }
}

<?php

namespace App\Repository;

use App\Entity\Config;
use App\Service\DebugLogger;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Config|null find($id, $lockMode = null, $lockVersion = null)
 * @method Config|null findOneBy(array $criteria, array $orderBy = null)
 * @method Config[]    findAll()
 * @method Config[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ConfigRepository extends ServiceEntityRepository
{
    private DebugLogger $debugLogger;

    public function __construct(ManagerRegistry $registry, DebugLogger $debugLogger)
    {
        parent::__construct($registry, Config::class);
        $this->debugLogger = $debugLogger;
    }

    public function deleteAll(): int
    {
        $this->debugLogger->logStart(__CLASS__, __FUNCTION__, []);
        $__debugReturn = null;
        try {
            $qb = $this->createQueryBuilder('c');

            $qb->delete();

            return $__debugReturn = $qb->getQuery()->getSingleScalarResult() ?? 0;
        } finally {
            $this->debugLogger->logEnd(__CLASS__, __FUNCTION__, $__debugReturn);
        }
    }

    public function findOneByAllowInstall($value): ?Config
    {
        $this->debugLogger->logStart(__CLASS__, __FUNCTION__, ['value' => $value]);
        $__debugReturn = null;
        try {
            return $__debugReturn = $this->createQueryBuilder('c')
                ->andWhere('c.name = :allow_install')
                ->setParameter('allow_install', $value)
                ->getQuery()
                ->getOneOrNullResult()
            ;
        } finally {
            $this->debugLogger->logEnd(__CLASS__, __FUNCTION__, $__debugReturn);
        }
    }

    public function findAlertDateRef()
    {
        $this->debugLogger->logStart(__CLASS__, __FUNCTION__, []);
        $__debugReturn = null;
        try {
            return $__debugReturn = $this->createQueryBuilder('c')
                ->select('c.value')
                ->where('c.name = :name')
                ->setParameter('name', 'alert_date_ref')
                ->getQuery()
                ->getOneOrNullResult();
        } finally {
            $this->debugLogger->logEnd(__CLASS__, __FUNCTION__, $__debugReturn);
        }
    }

    public function setAlertDateRef($newDate)
    {
        $this->debugLogger->logStart(__CLASS__, __FUNCTION__, ['newDate' => $newDate]);
        try {
            $entityManager = $this->getEntityManager();

            $config = $this->findOneBy(['name' => 'alert_date_ref']);

            if ($config) {
                $config->setValue($newDate);
                $entityManager->persist($config);
                $entityManager->flush();
            }
        } finally {
            $this->debugLogger->logEnd(__CLASS__, __FUNCTION__);
        }
    }

    public function findPager()
    {
        $this->debugLogger->logStart(__CLASS__, __FUNCTION__, []);
        $__debugReturn = null;
        try {
            return $__debugReturn = $this->createQueryBuilder('c')
                ->select('c.value')
                ->where('c.name = :name')
                ->setParameter('name', 'pager')
                ->getQuery()
                ->getOneOrNullResult();
        } finally {
            $this->debugLogger->logEnd(__CLASS__, __FUNCTION__, $__debugReturn);
        }
    }

    public function setPager($newPager)
    {
        $this->debugLogger->logStart(__CLASS__, __FUNCTION__, ['newPager' => $newPager]);
        try {
            $entityManager = $this->getEntityManager();

            $config = $this->findOneBy(['name' => 'pager']);

            if ($config) {
                $config->setValue($newPager);
                $entityManager->persist($config);
                $entityManager->flush();
            }
        } finally {
            $this->debugLogger->logEnd(__CLASS__, __FUNCTION__);
        }
    }

    public function getSearchLimit()
    {
        $this->debugLogger->logStart(__CLASS__, __FUNCTION__, []);
        $__debugReturn = null;
        try {
            return $__debugReturn = $this->createQueryBuilder('c')
                ->select('c.value')
                ->where('c.name = :name')
                ->setParameter('name', 'search_limit')
                ->getQuery()
                ->getOneOrNullResult();
        } finally {
            $this->debugLogger->logEnd(__CLASS__, __FUNCTION__, $__debugReturn);
        }
    }

    public function setSearchLimit($newLimit)
    {
        $this->debugLogger->logStart(__CLASS__, __FUNCTION__, ['newLimit' => $newLimit]);
        try {
            $entityManager = $this->getEntityManager();

            $config = $this->findOneBy(['name' => 'search_limit']);

            if ($config) {
                $config->setValue($newLimit);
                $entityManager->persist($config);
                $entityManager->flush();
            }
        } finally {
            $this->debugLogger->logEnd(__CLASS__, __FUNCTION__);
        }
    }

    public function getDebugMode(): bool
    {
        $this->debugLogger->logStart(__CLASS__, __FUNCTION__, []);
        $__debugReturn = null;
        try {
            $result = $this->createQueryBuilder('c')
                ->select('c.value')
                ->where('c.name = :name')
                ->setParameter('name', 'debug_mode')
                ->getQuery()
                ->getOneOrNullResult();

            return $__debugReturn = $result ? ($result['value'] === '1') : false;
        } finally {
            $this->debugLogger->logEnd(__CLASS__, __FUNCTION__, $__debugReturn);
        }
    }

    public function setDebugMode(bool $enabled): void
    {
        $this->debugLogger->logStart(__CLASS__, __FUNCTION__, ['enabled' => $enabled]);
        try {
            $entityManager = $this->getEntityManager();
            $config = $this->findOneBy(['name' => 'debug_mode']);

            if ($config) {
                $config->setValue($enabled ? '1' : '0');
            } else {
                $config = new Config();
                $config->setName('debug_mode');
                $config->setValue($enabled ? '1' : '0');
            }

            $entityManager->persist($config);
            $entityManager->flush();
        } finally {
            $this->debugLogger->logEnd(__CLASS__, __FUNCTION__);
        }
    }

    public function getLogLevel(): string
    {
        $this->debugLogger->logStart(__CLASS__, __FUNCTION__, []);
        $__debugReturn = null;
        try {
            $result = $this->createQueryBuilder('c')
                ->select('c.value')
                ->where('c.name = :name')
                ->setParameter('name', 'log_level')
                ->getQuery()
                ->getOneOrNullResult();

            return $__debugReturn = $result ? $result['value'] : 'debug';
        } finally {
            $this->debugLogger->logEnd(__CLASS__, __FUNCTION__, $__debugReturn);
        }
    }

    public function setLogLevel(string $level): void
    {
        $this->debugLogger->logStart(__CLASS__, __FUNCTION__, ['level' => $level]);
        try {
            $entityManager = $this->getEntityManager();
            $config = $this->findOneBy(['name' => 'log_level']);

            if ($config) {
                $config->setValue($level);
            } else {
                $config = new Config();
                $config->setName('log_level');
                $config->setValue($level);
            }

            $entityManager->persist($config);
            $entityManager->flush();
        } finally {
            $this->debugLogger->logEnd(__CLASS__, __FUNCTION__);
        }
    }
}

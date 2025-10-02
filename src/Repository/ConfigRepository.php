<?php

namespace App\Repository;

use App\Entity\Config;
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
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Config::class);
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function deleteAll(): int
    {
        $qb = $this->createQueryBuilder('c');

        $qb->delete();

        return $qb->getQuery()->getSingleScalarResult() ?? 0;
    }

    /**
     * @throws NonUniqueResultException
     */
    public function findOneByAllowInstall($value): ?Config
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.name = :allow_install')
            ->setParameter('allow_install', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function findAlertDateRef()
    {
        return $this->createQueryBuilder('c')
            ->select('c.value')
            ->where('c.name = :name')
            ->setParameter('name', 'alert_date_ref')
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function setAlertDateRef($newDate)
{
    $entityManager = $this->getEntityManager();

    // Supposons que votre entité de configuration s'appelle Config
    $config = $this->findOneBy(['name' => 'alert_date_ref']);

    if ($config) {
        $config->setValue($newDate);
        $entityManager->persist($config);
        $entityManager->flush();
    }
}

    public function findPager()
    {
        return $this->createQueryBuilder('c')
            ->select('c.value')
            ->where('c.name = :name')
            ->setParameter('name', 'pager')
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function setPager($newPager)
    {
        $entityManager = $this->getEntityManager();

        // Supposons que votre entité de configuration s'appelle Config
        $config = $this->findOneBy(['name' => 'pager']);

        if ($config) {
            $config->setValue($newPager);
            $entityManager->persist($config);
            $entityManager->flush();
        }
    }

    public function getSearchLimit()
    {
        return $this->createQueryBuilder('c')
            ->select('c.value')
            ->where('c.name = :name')
            ->setParameter('name', 'search_limit')
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function setSearchLimit($newLimit)
    {
        $entityManager = $this->getEntityManager();

        // Supposons que votre entité de configuration s'appelle Config
        $config = $this->findOneBy(['name' => 'search_limit']);

        if ($config) {
            $config->setValue($newLimit);
            $entityManager->persist($config);
            $entityManager->flush();
        }
    }
}
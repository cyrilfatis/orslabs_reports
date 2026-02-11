<?php

namespace App\Repository;

use App\Entity\PerformanceMetric;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PerformanceMetric>
 */
class PerformanceMetricRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PerformanceMetric::class);
    }

    /**
     * Retourne la métrique la plus récente pour une source donnée
     */
    public function findLatestBySource(string $source): ?PerformanceMetric
    {
        return $this->createQueryBuilder('pm')
            ->andWhere('pm.source = :source')
            ->setParameter('source', $source)
            ->orderBy('pm.period', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Retourne les N dernières métriques pour une source
     */
    public function findLastNBySource(string $source, int $limit = 6): array
    {
        return $this->createQueryBuilder('pm')
            ->andWhere('pm.source = :source')
            ->setParameter('source', $source)
            ->orderBy('pm.period', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Retourne la métrique pour une période et source donnée
     */
    public function findByPeriodAndSource(string $period, string $source): ?PerformanceMetric
    {
        return $this->createQueryBuilder('pm')
            ->andWhere('pm.period = :period')
            ->andWhere('pm.source = :source')
            ->setParameter('period', $period)
            ->setParameter('source', $source)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Retourne toutes les métriques triées par période DESC
     */
    public function findAllSorted(): array
    {
        return $this->createQueryBuilder('pm')
            ->orderBy('pm.period', 'DESC')
            ->addOrderBy('pm.source', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Retourne les métriques SEO des N derniers mois (pour les graphiques)
     */
    public function findSeoHistory(int $months = 12): array
    {
        return $this->createQueryBuilder('pm')
            ->andWhere('pm.source = :source')
            ->setParameter('source', PerformanceMetric::SOURCE_SEO)
            ->orderBy('pm.period', 'ASC')
            ->setMaxResults($months)
            ->getQuery()
            ->getResult();
    }

    /**
     * Retourne les métriques LinkedIn des N derniers mois (pour les graphiques)
     */
    public function findLinkedinHistory(int $months = 12): array
    {
        return $this->createQueryBuilder('pm')
            ->andWhere('pm.source = :source')
            ->setParameter('source', PerformanceMetric::SOURCE_LINKEDIN)
            ->orderBy('pm.period', 'ASC')
            ->setMaxResults($months)
            ->getQuery()
            ->getResult();
    }
}

<?php

namespace App\Repository;

use App\Entity\Report;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Report>
 */
class ReportRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Report::class);
    }

    /**
     * Trouve tous les rapports actifs triés par date décroissante
     */
    public function findAllActive(): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('r.reportDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve un rapport par sa période (ex: 2025-11)
     */
    public function findByPeriod(string $period): ?Report
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.period = :period')
            ->andWhere('r.isActive = :active')
            ->setParameter('period', $period)
            ->setParameter('active', true)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Compte le nombre total de rapports actifs
     */
    public function countActive(): int
    {
        return $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->andWhere('r.isActive = :active')
            ->setParameter('active', true)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Trouve le dernier rapport publié
     */
    public function findLatest(): ?Report
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('r.reportDate', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Trouve les N derniers rapports
     */
    public function findLatestN(int $limit = 5): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('r.reportDate', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}

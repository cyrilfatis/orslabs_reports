<?php

namespace App\Repository;

use App\Entity\Document;
use App\Entity\DocumentCategory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class DocumentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Document::class);
    }

    /** @return Document[] Documents non supprimés d'une catégorie */
    public function findByCategory(DocumentCategory $category): array
    {
        return $this->createQueryBuilder('d')
            ->where('d.category = :cat')
            ->andWhere('d.deletedAt IS NULL')
            ->setParameter('cat', $category)
            ->orderBy('d.uploadedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /** @return Document[] Tous les documents non supprimés */
    public function findAllActive(): array
    {
        return $this->createQueryBuilder('d')
            ->where('d.deletedAt IS NULL')
            ->leftJoin('d.category', 'c')
            ->addSelect('c')
            ->leftJoin('d.uploadedBy', 'u')
            ->addSelect('u')
            ->orderBy('d.uploadedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}

<?php

namespace App\Repository;

use App\Entity\MailingFile;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class MailingFileRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MailingFile::class);
    }

    /** @return MailingFile[] */
    public function findAllOrdered(): array
    {
        return $this->createQueryBuilder('m')
            ->orderBy('CASE WHEN m.sentAt IS NULL THEN 0 ELSE 1 END', 'ASC')
            ->addOrderBy('m.sentAt', 'DESC')
            ->addOrderBy('m.id', 'DESC')
            ->getQuery()
            ->getResult();
    }
}

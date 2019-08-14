<?php

namespace Adshares\Adclassify\Repository;

use Adshares\Adclassify\Entity\Ad;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

class AdRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Ad::class);
    }

    public function findByChecksum(string $checksum): ?Ad
    {
        return $this->findOneBy(['checksum' => $checksum]);
    }
}

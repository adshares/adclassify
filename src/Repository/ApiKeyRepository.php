<?php

namespace Adshares\Adclassify\Repository;

use Adshares\Adclassify\Entity\ApiKey;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

class ApiKeyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ApiKey::class);
    }

    public function findByName(string $name): ?ApiKey
    {
        return $this->findOneBy(['name' => $name]);
    }
}

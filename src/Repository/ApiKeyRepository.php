<?php

namespace Adshares\Adclassify\Repository;

use Adshares\Adclassify\Entity\ApiKey;
use Adshares\Adclassify\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

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

    public function createApiKey(User $user): ApiKey
    {
        $apiKey = new ApiKey();
        $apiKey->setUser($user);
        $apiKey->setName(trim(base64_encode(random_bytes(8)), '='));
        $apiKey->setSecret(trim(base64_encode(random_bytes(16)), '='));

        $this->_em->persist($apiKey);
        $this->_em->flush();

        return $apiKey;
    }
}

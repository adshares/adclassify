<?php

namespace Adshares\Adclassify\Repository;

use Adshares\Adclassify\Entity\Request;
use Adshares\Adclassify\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

class RequestRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Request::class);
    }

    public function findPending(): array
    {
        return $this->findBy(['status' => Request::STATUS_NEW]);
    }

    public function findPendingDuplicates(User $user, string $bannerId): array
    {
        return $this->findBy([
            'user' => $user,
            'bannerId' => $bannerId,
            'status' => Request::STATUS_NEW,
        ]);
    }
}

<?php

namespace Adshares\Adclassify\Repository;

use Adshares\Adclassify\Entity\Request;
use Adshares\Adclassify\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\Tools\Pagination\Paginator;

class RequestRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Request::class);
    }

    public function findPaginated(
        int $limit = 2,
        int $offset = 0,
        ?string $sort = null,
        ?string $order = null
    ): Paginator {
        $query = $this->createQueryBuilder('e')
            ->setFirstResult($offset)
            ->setMaxResults($limit);

        if ($sort !== null) {
            $query->orderBy('e.' . $sort, $order);
        }

        return new Paginator($query, $fetchJoinCollection = false);
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

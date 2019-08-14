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

    public function findNews(): array
    {
        return $this->findBy(['status' => Request::STATUS_NEW]);
    }

    public function findByCampaign(Request $request): array
    {
        return $this->findBy(
            [
                'status' => [Request::STATUS_PENDING, Request::STATUS_PROCESSED],
                'user' => $request->getUser(),
                'campaignId' => $request->getCampaignId(),
            ],
            ['status' => 'DESC']
        );
    }

    public function findPrevPending(Request $request = null): ?Request
    {
        return $this->findOneBy(
            ['status' => Request::CALLBACK_PENDING],
            ['createdAt' => 'DESC']
        );
    }

    public function findNextPending(Request $request = null): ?Request
    {
        return $this->findOneBy(
            ['status' => Request::CALLBACK_PENDING],
            ['createdAt' => 'DESC']
        );
    }

    public function findPendingDuplicates(User $user, string $bannerId): array
    {
        return $this->findBy([
            'user' => $user,
            'bannerId' => $bannerId,
            'status' => [Request::STATUS_NEW, Request::STATUS_PENDING],
        ]);
    }

    public function findReadyToCallback(int $limit = null): array
    {
        return $this->findBy([
            'callbackStatus' => Request::CALLBACK_PENDING,
        ], null, $limit);
    }

    public function saveBatch(array $requests): void
    {
        foreach ($requests as $request) {
            $this->_em->persist($request);
        }
        $this->_em->flush();
    }
}

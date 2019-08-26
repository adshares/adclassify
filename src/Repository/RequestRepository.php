<?php

namespace Adshares\Adclassify\Repository;

use Adshares\Adclassify\Entity\Ad;
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

    public function findByAd(Ad $ad): array
    {
        return $this->findBy(
            [
                'status' => [Request::STATUS_PROCESSED, Request::STATUS_NEW, Request::STATUS_PENDING],
                'ad' => $ad,
            ]
        );
    }

    public function findByCampaign(Request $request): array
    {
        return $this->findBy(
            [
                'status' => [Request::STATUS_PROCESSED, Request::STATUS_PENDING],
                'user' => $request->getUser(),
                'campaignId' => $request->getCampaignId(),
            ],
            ['status' => 'DESC', 'id' => 'ASC']
        );
    }

    public function findNextPending(Request $request = null, $newer = true): ?Request
    {
        $qb = $this->createQueryBuilder('e');
        $qb->where('e.status = :status');
        $qb->setParameter('status', Request::STATUS_PENDING);
        if ($request !== null) {
            $qb->andWhere($newer ? 'e.id > :requestId' : 'e.id < :requestId');
            $qb->andWhere($qb->expr()->orX('e.user != :userId', 'e.campaignId != :campaignId'));
            $qb->setParameter('requestId', $request->getId());
            $qb->setParameter('userId', $request->getUser());
            $qb->setParameter('campaignId', $request->getCampaignId());
        }
        $qb->orderBy('e.id', $newer ? 'ASC' : 'DESC');
        $qb->setMaxResults(1);

        return $qb->getQuery()->getOneOrNullResult();
    }

    public function findPendingDuplicates(User $user, string $bannerId): array
    {
        return $this->findBy([
            'user' => $user,
            'bannerId' => $bannerId,
            'status' => [Request::STATUS_NEW, Request::STATUS_PENDING],
        ]);
    }

    public function findReadyToProcess(int $limit = null, bool $includeFailed = false): array
    {
        $status = [Request::STATUS_NEW];
        if ($includeFailed) {
            $status[] = Request::STATUS_FAILED;
        }

        return $this->findBy([
            'status' => $status,
        ], null, $limit);
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

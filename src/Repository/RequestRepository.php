<?php

namespace Adshares\Adclassify\Repository;

use Adshares\Adclassify\Entity\Ad;
use Adshares\Adclassify\Entity\Request;
use Adshares\Adclassify\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;

class RequestRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Request::class);
    }

    public function getFindBuilder(
        ?string $query = null
    ): QueryBuilder {
        $builder = $this->createQueryBuilder('r');
        $query = self::prepareQuery($query ?? '');
        if (!empty($query)) {
            $builder->leftJoin('r.ad', 'a');
            $builder->where('HEX(r.campaignId) LIKE :query');
            $builder->orWhere('HEX(r.bannerId) LIKE :query');
            $builder->orWhere('r.serveUrl LIKE :query');
            $builder->orWhere('r.landingUrl LIKE :query');
            $builder->orWhere('HEX(a.checksum) LIKE :query');
//            $builder->orWhere('HEX(a.content) LIKE :hexQuery');
            $builder->setParameter('query', sprintf('%%%s%%', $query));
//            $builder->setParameter('hexQuery', sprintf('%%%s%%', bin2hex($query)));
        }
        return $builder;
    }

    public function findPaginated(
        ?string $query = null,
        int $limit = 2,
        int $offset = 0,
        ?string $sort = null,
        ?string $order = null
    ): Paginator {
        $builder = $this->getFindBuilder($query)
            ->setFirstResult($offset)
            ->setMaxResults($limit);
        if ($sort !== null) {
            $builder->orderBy('r.' . $sort, $order);
        }
        return new Paginator($builder, false);
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
        return $this->findBy(
            [
                'user' => $user,
                'bannerId' => $bannerId,
                'status' => [Request::STATUS_NEW, Request::STATUS_PENDING],
            ]
        );
    }

    public function findReadyToProcess(int $limit = null, bool $includeFailed = false): array
    {
        $status = [Request::STATUS_NEW];
        if ($includeFailed) {
            $status[] = Request::STATUS_FAILED;
        }

        return $this->findBy(
            [
                'status' => $status,
            ],
            null,
            $limit
        );
    }

    public function findReadyToCallback(int $limit = null): array
    {
        return $this->findBy(
            [
                'callbackStatus' => Request::CALLBACK_PENDING,
            ],
            null,
            $limit
        );
    }

    public function saveBatch(array $requests): void
    {
        foreach ($requests as $request) {
            $this->_em->persist($request);
        }
        $this->_em->flush();
    }

    private static function prepareQuery(string $query): string
    {
        $query = trim($query);

        if (preg_match('/^(0x|x)?([0-9a-fA-F]+)$/', $query, $matches)) {
            return $matches[2];
        }

        if (false !== filter_var($query, FILTER_VALIDATE_URL)) {
            $parts = parse_url($query);
            if (strpos($parts['path'] ?? '', '/serve/') === 0) {
                return $parts['path'];
            }
            return sprintf('//%s%s', $parts['host'] ?? '', $parts['path'] ?? '');
        }


        return preg_replace('/#.*$/', '', $query);
    }
}

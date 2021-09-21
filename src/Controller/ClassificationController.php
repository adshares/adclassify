<?php

namespace Adshares\Adclassify\Controller;

use Adshares\Adclassify\Entity\Request as ClassificationRequest;
use Adshares\Adclassify\Repository\RequestRepository;
use Adshares\Adclassify\Repository\TaxonomyRepository;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/classification", name="classification_")
 */
class ClassificationController extends AbstractController
{
    public const CATEGORY_REJECT = 'reject';

    private RequestRepository $requestRepository;
    private TaxonomyRepository $taxonomyRepository;

    public function __construct(
        RequestRepository $requestRepository,
        TaxonomyRepository $taxonomyRepository
    ) {
        $this->requestRepository = $requestRepository;
        $this->taxonomyRepository = $taxonomyRepository;
    }

    /**
     * @Route("/{requestId}", requirements={"requestId"="\d+"}, methods={"GET"}, name="index")
     */
    public function index(Request $request, ?int $requestId = null): Response
    {
        $query = $request->query->get('query', null);
        if ($requestId !== null) {
            if (($campaign = $this->requestRepository->find($requestId)) === null) {
                throw new NotFoundHttpException(sprintf('Cannot find request #%d', $requestId));
            }
        } else {
            $campaign = $this->requestRepository->findNextPending();
        }

        $requests = [];
        $prevCampaign = $nextCampaign = null;

        if ($campaign !== null) {
            if ($campaign->getStatus() === ClassificationRequest::STATUS_REJECTED) {
                $requests = [$campaign];
            } else {
                $requests = $this->requestRepository->findByCampaign($campaign);
            }
            $prevCampaign = $this->requestRepository->findNextPending($campaign, false);
            $nextCampaign = $this->requestRepository->findNextPending($campaign);
        }

        $categories = $this->taxonomyRepository->getCatgories();
        $categorySafe = array_pop($categories);
        $categoryReject = [
            'key' => self::CATEGORY_REJECT,
            'label' => 'Reject',
            'description' => 'Invalid, below standards'
        ];
        $qualityLevels = $this->taxonomyRepository->getQualityLevels();

        return $this->render(
            'classification/index.html.twig',
            [
                'requests' => $requests,
                'campaign' => $campaign,
                'prevCampaign' => $prevCampaign,
                'nextCampaign' => $nextCampaign,
                'categories' => $categories,
                'categorySafe' => $categorySafe,
                'categoryReject' => $categoryReject,
                'qualityLevels' => $qualityLevels,
                'query' => $query,
            ]
        );
    }

    /**
     * @Route("/", methods={"POST"}, name="save")
     */
    public function save(Request $request): Response
    {
        $submittedToken = $request->request->get('token');
        $classifications = $request->request->get('classifications', []);

        if (!$this->isCsrfTokenValid('panel', $submittedToken)) {
            throw new RuntimeException('Invalid CSRF token');
        }

        $entityManager = $this->getDoctrine()->getManager();
        $categoryTaxonomy = array_map(
            function ($category) {
                return $category['key'];
            },
            $this->taxonomyRepository->getCatgories()
        );
        $qualityTaxonomy = array_map(
            function ($category) {
                return $category['key'];
            },
            $this->taxonomyRepository->getQualityLevels()
        );

        $cRequest = null;
        foreach ($classifications as $id => $categories) {
            /* @var $cRequest ClassificationRequest */
            if (($cRequest = $this->requestRepository->find($id)) === null) {
                throw new RuntimeException('Invalid classification request id');
            }

            $ad = $cRequest->getAd();

            if (isset($categories[TaxonomyRepository::CATEGORY][self::CATEGORY_REJECT])) {
                if ($ad->isRejected()) {
                    break;
                }
                $ad->setRejected(true);
            } else {
                $ad->setRejected(false);
                if (isset($categories[TaxonomyRepository::CATEGORY][TaxonomyRepository::CATEGORY_SAFE])) {
                    $category = [TaxonomyRepository::CATEGORY_SAFE];
                } else {
                    $category = array_values(
                        array_intersect($categoryTaxonomy, array_keys($categories[TaxonomyRepository::CATEGORY]))
                    );
                }
                $quality = array_values(
                    array_intersect($qualityTaxonomy, array_keys($categories[TaxonomyRepository::QUALITY]))
                );
                $keywords = [
                    TaxonomyRepository::CATEGORY => $category,
                    TaxonomyRepository::QUALITY => $quality
                ];

                if ($ad->getKeywords() === $keywords) {
                    break;
                }
                $ad->setKeywords($keywords);
            }

            $ad->setProcessed(true);
            $entityManager->persist($ad);

            $requests = $this->requestRepository->findByAd($ad);
            $requests[] = $cRequest;

            foreach ($requests as $aRequest) {
                /* @var $aRequest ClassificationRequest */
                if ($cRequest->getAd()->isRejected()) {
                    $aRequest->setStatus(ClassificationRequest::STATUS_REJECTED);
                    $aRequest->setInfo('Rejected by classifier');
                } else {
                    $aRequest->setStatus(ClassificationRequest::STATUS_PROCESSED);
                    $aRequest->setInfo(null);
                }
                $entityManager->persist($aRequest);
            }
        }

        $entityManager->flush();

        $next = $this->requestRepository->findNextPending($cRequest);

        return new RedirectResponse(
            $this->generateUrl(
                'classification_index',
                [
                    'requestId' => $next ? $next->getId() : null
                ]
            )
        );
    }

    /**
     * @Route("/status", methods={"GET"}, name="status")
     */
    public function status(Request $request): Response
    {
        $limit = 50;
        $page = max(1, (int)$request->query->get('page', 1));
        $sort = 'id';
        $order = 'desc';

        $query = $request->query->get('query', null);

        $requests = $this->requestRepository->findPaginated($query, $limit, ($page - 1) * $limit, $sort, $order);

        return $this->render(
            'classification/status.html.twig',
            [
                'requests' => $requests,
                'currentPage' => $page,
                'totalPages' => ceil($requests->count() / $limit),
                'query' => $query,
            ]
        );
    }

    /**
     * @Route("/search", methods={"GET", "POST"}, name="search")
     */
    public function search(Request $request): Response
    {
        $query = $request->query->get('query', null);
        $builder = $this->requestRepository->getFindBuilder($query);

        $builder->select('r.campaignId, MIN(r.id) AS id')
            ->groupBy('r.campaignId');

        $result = $builder->getQuery()->getResult();

        if (count($result) === 1) {
            $route = 'classification_index';
            $requestId = $result[0]['id'];
        } else {
            $route = 'classification_status';
            $requestId = null;
        }

        return new RedirectResponse(
            $this->generateUrl(
                $route,
                [
                    'requestId' => $requestId,
                    'query' => $query,
                ]
            )
        );
    }
}

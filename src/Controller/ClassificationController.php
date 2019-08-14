<?php

namespace Adshares\Adclassify\Controller;

use Adshares\Adclassify\Repository\AdRepository;
use Adshares\Adclassify\Repository\RequestRepository;
use Adshares\Adclassify\Repository\TaxonomyRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ClassificationController extends AbstractController
{

    /**
     * @var AdRepository
     */
    private $classificationRepository;

    /**
     * @var RequestRepository
     */
    private $requestRepository;

    /**
     * @var TaxonomyRepository
     */
    private $taxonomyRepository;

    public function __construct(
        AdRepository $classificationRepository,
        RequestRepository $requestRepository,
        TaxonomyRepository $taxonomyRepository
    ) {
        $this->classificationRepository = $classificationRepository;
        $this->requestRepository = $requestRepository;
        $this->taxonomyRepository = $taxonomyRepository;
    }

    public function index(?string $requestId = null): Response
    {
        if ($requestId !== null) {
            if (($request = $this->requestRepository->find($requestId)) === null) {
                throw new NotFoundHttpException(sprintf('Cannot find request #%d', $requestId));
            }
        } else {
            $request = $this->requestRepository->findNextPending();
        }

        $campaign = [];
        $prevCampaign = $nextCampaign = null;

        if ($campaign !== null) {
            $requests = $this->requestRepository->findByCampaign($request);
            $prevCampaign = $this->requestRepository->findNextPending($request, false);
            $nextCampaign = $this->requestRepository->findNextPending($request);
        }

        $categories = $this->taxonomyRepository->getCatgories();

        return $this->render('classification/index.html.twig', [
            'requests' => $requests,
            'campaign' => $request,
            'prevCampaign' => $prevCampaign,
            'nextCampaign' => $nextCampaign,
            'categories' => $categories,
        ]);
    }

    public function save(Request $request): Response
    {
        dump($request);exit;

        return new RedirectResponse();
    }

    public function status(Request $request): Response
    {
        $limit = 50;
        $page = max(1, (int)$request->query->get('page', 1));
        $sort = 'id';
        $order = 'desc';

        $requests = $this->requestRepository->findPaginated($limit, ($page - 1) * $limit, $sort, $order);

        return $this->render('classification/status.html.twig', [
            'requests' => $requests,
            'currentPage' => $page,
            'totalPages' => ceil($requests->count() / $limit),
        ]);
    }
}

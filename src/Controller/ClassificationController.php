<?php

namespace Adshares\Adclassify\Controller;

use Adshares\Adclassify\Repository\ClassificationRepository;
use Adshares\Adclassify\Repository\RequestRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ClassificationController extends AbstractController
{

    /**
     * @var ClassificationRepository
     */
    private $classificationRepository;

    /**
     * @var RequestRepository
     */
    private $requestRepository;

    public function __construct(
        ClassificationRepository $classificationRepository,
        RequestRepository $requestRepository
    ) {
        $this->classificationRepository = $classificationRepository;
        $this->requestRepository = $requestRepository;
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
            $prevCampaign = $this->requestRepository->findNextPending($request);
            $nextCampaign = $this->requestRepository->findPrevPending($request);
        }

        return $this->render('classification/index.html.twig', [
            'requests' => $requests,
            'campaign' => $request,
            'prevCampaign' => $prevCampaign,
            'nextCampaign' => $nextCampaign,
        ]);
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

<?php

namespace Adshares\Adclassify\Controller;

use Adshares\Adclassify\Repository\ClassificationRepository;
use Adshares\Adclassify\Repository\RequestRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

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

    public function index(): Response
    {
        return $this->render('classification/index.html.twig', []);
    }

    public function status(): Response
    {

        $requests = $this->requestRepository->findBy([], ['id' => 'DESC'], 20, 0);

        return $this->render('classification/status.html.twig', [
            'requests' => $requests,
        ]);
    }
}

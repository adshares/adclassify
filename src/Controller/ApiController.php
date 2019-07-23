<?php

namespace Adshares\Adclassify\Controller;

use Adshares\Adclassify\Entity\Classification;
use Adshares\Adclassify\Entity\Request as ClassificationRequest;
use Adshares\Adclassify\Repository\ClassificationRepository;
use Adshares\Adclassify\Repository\RequestRepository;
use Adshares\Adclassify\Repository\TaxonomyRepository;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class ApiController extends AbstractController
{
    /**
     * @var LoggerInterface
     */
    private $logger;

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
        RequestRepository $requestRepository,
        LoggerInterface $logger
    ) {
        if ($logger === null) {
            $logger = new NullLogger();
        }
        $this->classificationRepository = $classificationRepository;
        $this->requestRepository = $requestRepository;
        $this->logger = $logger;
    }

    public function getTaxonomy(TaxonomyRepository $repository): Response
    {
        $taxonomy = [
            'meta' => [
                'name' => getenv('TAXONOMY_NAME'),
                'version' => getenv('TAXONOMY_VERSION'),
            ],
            'data' => $repository->getTaxonomy(),
        ];

        return new JsonResponse($taxonomy);
    }

    public function postRequests(Request $request): Response
    {
        $data = json_decode($request->getContent(), true);

        if ($data === null) {
            throw new BadRequestHttpException(json_last_error_msg());
        }

        $this->validRequest($data);
        foreach ($data['banners'] as $banner) {
            $this->validBanner($banner);
        }
        foreach ($data['banners'] as $banner) {
            $this->handleBanner($banner, $data['callback_url']);
        }

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    private function validRequest(array $request): void
    {
        if (empty($request['callback_url']) || filter_var($request['callback_url'], FILTER_VALIDATE_URL) === false) {
            throw new UnprocessableEntityHttpException('Invalid callback URL');
        }

        //TODO check if callback URL is legal

        if (empty($request['banners'])) {
            throw new UnprocessableEntityHttpException('No banners were received');
        }
    }

    private function validBanner(array $banner): void
    {
        if (empty($banner['id'])) {
            throw new UnprocessableEntityHttpException('Empty banner id');
        }

        if (!preg_match('/^[0-9A-F]{32}$/i', $banner['id'])) {
            throw new UnprocessableEntityHttpException(sprintf('Invalid banner id (in %s)', $banner['id']));
        }

        if (empty($banner['checksum']) || !preg_match('/^[0-9A-F]{40}$/i', $banner['checksum'])) {
            throw new UnprocessableEntityHttpException(sprintf('Invalid banner checksum (in %s)', $banner['id']));
        }

        if (empty($banner['type']) || !in_array($banner['type'], ['image', 'html'])) {
            throw new UnprocessableEntityHttpException(sprintf('Invalid banner type (in %s)', $banner['id']));
        }

        if (empty($banner['campaign_id']) || !preg_match('/^[0-9A-F]{32}$/i', $banner['campaign_id'])) {
            throw new UnprocessableEntityHttpException(sprintf('Invalid campaign id (in %s)', $banner['id']));
        }

        if (empty($banner['serve_url']) || filter_var($banner['serve_url'], FILTER_VALIDATE_URL) === false) {
            throw new UnprocessableEntityHttpException(sprintf('Invalid serve URL (in %s)', $banner['id']));
        }

        if (empty($banner['landing_url']) || filter_var($banner['landing_url'], FILTER_VALIDATE_URL) === false) {
            throw new UnprocessableEntityHttpException(sprintf('Invalid landing URL (in %s)', $banner['id']));
        }
    }

    private function handleBanner(array $banner, string $callbackUrl): void
    {
        $entityManager = $this->getDoctrine()->getManager();

        $classification = $this->classificationRepository->findByChecksum(hex2bin($banner['checksum']));
        if ($classification === null) {
            $classification = new Classification();
            $classification->setChecksum(hex2bin($banner['checksum']));
            $entityManager->persist($classification);
        }

        foreach ($this->requestRepository->findPendingDuplicates(
            $this->getUser(),
            hex2bin($banner['id'])
        ) as $duplicate) {
            /* @var $duplicate ClassificationRequest */
            $duplicate->setStatus(ClassificationRequest::STATUS_CANCELED);
            $entityManager->persist($duplicate);
        }

        $request = new ClassificationRequest();
        $request->setUser($this->getUser());
        $request->setBannerId(hex2bin($banner['id']));
        $request->setClassification($classification);
        $request->setCallbackUrl($callbackUrl);
        $request->setServeUrl($banner['serve_url']);
        $request->setType($banner['type']);
        $request->setLandingUrl($banner['landing_url']);
        $request->setCampaignId(hex2bin($banner['campaign_id']));
        $request->setServeUrl($banner['serve_url']);
        $entityManager->persist($request);

        $entityManager->flush();
    }
}

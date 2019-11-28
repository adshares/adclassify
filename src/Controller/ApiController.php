<?php

namespace Adshares\Adclassify\Controller;

use Adshares\Adclassify\Entity\Ad;
use Adshares\Adclassify\Entity\Request as ClassificationRequest;
use Adshares\Adclassify\Repository\AdRepository;
use Adshares\Adclassify\Repository\RequestRepository;
use Adshares\Adclassify\Repository\TaxonomyRepository;
use Adshares\Adclassify\Service\Signer;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class ApiController extends AbstractController
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var AdRepository
     */
    private $classificationRepository;

    /**
     * @var RequestRepository
     */
    private $requestRepository;

    /**
     * @var Signer
     */
    private $signer;

    public function __construct(
        AdRepository $classificationRepository,
        RequestRepository $requestRepository,
        Signer $signer,
        LoggerInterface $logger
    ) {
        if ($logger === null) {
            $logger = new NullLogger();
        }
        $this->classificationRepository = $classificationRepository;
        $this->requestRepository = $requestRepository;
        $this->signer = $signer;
        $this->logger = $logger;
    }

    public function getTaxonomy(TaxonomyRepository $repository): Response
    {
        $taxonomy = [
            'meta' => [
                'name' => $_ENV['TAXONOMY_NAME'],
                'version' => $_ENV['TAXONOMY_VERSION'],
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

        $response = new JsonResponse(null, Response::HTTP_NO_CONTENT);

        foreach ($data['banners'] as $banner) {
            $this->handleBanner($banner, $data['callback_url']);
        }

        return $response;
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

        if (empty($banner['size'])) {
            throw new UnprocessableEntityHttpException(sprintf('Invalid banner size (in %s)', $banner['id']));
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

        $duplicates = $this->requestRepository->findPendingDuplicates($this->getUser(), hex2bin($banner['id']));

        $classification = $this->classificationRepository->findByChecksum(hex2bin($banner['checksum']));
        if ($classification === null) {
            $classification = new Ad();
            $classification->setSize($banner['size']);
            $classification->setChecksum(hex2bin($banner['checksum']));
            $entityManager->persist($classification);
        }

        $request = new ClassificationRequest();
        $request->setUser($this->getUser());
        $request->setBannerId(hex2bin($banner['id']));
        $request->setAd($classification);
        $request->setCallbackUrl($callbackUrl);
        $request->setServeUrl($banner['serve_url']);
        $request->setType($banner['type']);
        $request->setLandingUrl($banner['landing_url']);
        $request->setCampaignId(hex2bin($banner['campaign_id']));
        $request->setServeUrl($banner['serve_url']);
        $entityManager->persist($request);

        $entityManager->flush();

        foreach ($duplicates as $duplicate) {
            /* @var $duplicate ClassificationRequest */
            $duplicate->setStatus(ClassificationRequest::STATUS_CANCELED);
            $duplicate->setInfo(sprintf('Overwritten by request #%d', $request->getId()));
            $entityManager->persist($duplicate);
        }
        $entityManager->flush();
    }
}

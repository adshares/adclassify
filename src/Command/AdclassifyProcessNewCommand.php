<?php

namespace Adshares\Adclassify\Command;

use Adshares\Adclassify\Entity\Request as ClassificationRequest;
use Adshares\Adclassify\Repository\RequestRepository;
use Adshares\Adclassify\Service\Signer;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class AdclassifyProcessNewCommand extends Command
{
    protected static $defaultName = 'app:process:new';

    protected RequestRepository $requestRepository;
    protected Signer $signer;
    protected ?LoggerInterface $logger;

    public function __construct(
        RequestRepository $requestRepository,
        Signer $signer,
        string $name = null,
        LoggerInterface $logger = null
    ) {
        parent::__construct($name);
        if ($logger === null) {
            $logger = new NullLogger();
        }
        $this->logger = $logger;
        $this->requestRepository = $requestRepository;
        $this->signer = $signer;
    }

    protected function configure()
    {
        $this
            ->setDescription('Processing new requests')
            ->addOption('chunk', 'c', InputOption::VALUE_REQUIRED, 'Size of processed chunk', 500)
            ->addOption('retry', 'r', InputOption::VALUE_NONE, 'Retry process failed requests');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $chunk = max(1, (int)$input->getOption('chunk'));
        $info = sprintf(
            'Processing new requests with chunk size <info>%d</info>',
            $chunk
        );
        $this->logger->info(strip_tags($info));
        $io->comment($info);

        $success = $failed = $i = 0;
        do {
            $i++;
            $requests = $this->requestRepository->findReadyToProcess($chunk, $input->getOption('retry'));
            $size = count($requests);
            if ($size === 0) {
                break;
            }
            $io->comment(sprintf('Processing %d requests [%d]', $size, $i));
            $processed = $this->processRequests($requests, $io);
            $success += $processed;
            $failed += $size - $processed;
        } while ($size === $chunk);

        $info = sprintf('Processed %d requests, %d failed', $success, $failed);
        $this->logger->info($info);
        $io->success($info);

        return Command::SUCCESS;
    }

    private function processRequests(array $requests, SymfonyStyle $io): int
    {
        $failed = 0;
        foreach ($requests as $request) {
            /* @var $request ClassificationRequest */

            $request->setInfo(null);
            if ($request->getAd()->getContent() !== null) {
                if ($request->getAd()->isProcessed()) {
                    if ($request->getAd()->isRejected()) {
                        $request->setStatus(ClassificationRequest::STATUS_REJECTED);
                        $request->setInfo('Rejected by classifier');
                    } else {
                        $request->setStatus(ClassificationRequest::STATUS_PROCESSED);
                        $request->setInfo('Existing classification was used');
                    }
                } else {
                    $request->setStatus(ClassificationRequest::STATUS_PENDING);
                }
            } else {
                if (($content = $this->downloadContent($request)) !== null) {
                    $request->getAd()->setContent($content);
                    $request->setStatus(ClassificationRequest::STATUS_PENDING);
                } else {
                    $request->setStatus(ClassificationRequest::STATUS_FAILED);
                    $failed++;
                    $io->warning($request->getInfo());
                }
            }
        }
        $this->requestRepository->saveBatch($requests);

        return count($requests) - $failed;
    }

    private function downloadContent(ClassificationRequest $request): ?string
    {
        $httpClient = HttpClient::create(['verify_peer' => false]);
        $content = null;

        try {
            $response = $httpClient->request('GET', $request->getServeUrl());
            $content = $response->getContent();
        } catch (TransportExceptionInterface $exception) {
            $request->setInfo($exception->getMessage());
        } catch (HttpExceptionInterface $exception) {
            $request->setInfo($exception->getMessage());
        }

        if ($content !== null && !$this->signer->checkContent($content, $request->getAd()->getChecksum())) {
            $content = null;
            $request->setInfo('Invalid checksum');
        }

        return $content;
    }
}

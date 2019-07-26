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
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class AdclassifyCallbackCommand extends Command
{
    protected static $defaultName = 'app:callback';

    /**
     * @var RequestRepository
     */
    protected $requestRepository;

    /**
     * @var Signer
     */
    protected $signer;

    /**
     * @var LoggerInterface
     */
    protected $logger;

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
            ->setDescription('Sends processed requests back to adservers')
            ->addOption('chunk', 'c', InputOption::VALUE_REQUIRED, 'Size of processed chunk', 500);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $chunk = max(1, (int)$input->getOption('chunk'));
        $info = sprintf(
            'Sending processed requests back to adservers with chunk size <info>%d</info>',
            $chunk
        );
        $this->logger->info(strip_tags($info));
        $io->comment($info);

        $success = $failed = $i = 0;
        do {
            $i++;
            $requests = $this->requestRepository->findReadyToCallback($chunk);
            $size = count($requests);
            if ($size === 0) {
                break;
            }
            $io->comment(sprintf('Processing %d requests [%d]', $size, $i));
            $sent = $this->processRequests($requests, $io);
            $success += $sent;
            $failed += $size - $sent;
        } while ($size === $chunk);

        $info = sprintf('Sent %d requests, %d failed', $success, $failed);
        $this->logger->info($info);
        $io->success($info);
    }

    private function processRequests(array $requests, SymfonyStyle $io): int
    {
        $adservers = $origin = [];
        foreach ($requests as $request) {
            /* @var $request ClassificationRequest */

            $id = bin2hex($request->getBannerId());
            $data = ['id' => $id];
            if ($request->getStatus() === ClassificationRequest::STATUS_PROCESSED) {
                $data['keywords'] = $request->getClassification()->getKeywords();
                $data['signature'] = $this->signer->signClassification(clone $request->getClassification());
            } else {
                $data['error'] = [
                    'code' => $request->getStatus(),
                    'message' => $request->getInfo(),
                ];
            }

            $adservers[$request->getCallbackUrl()][] = $data;
            $origin[$request->getCallbackUrl()][$id] = $request;
        }

        $sent = 0;
        $httpClient = HttpClient::create();
        foreach ($adservers as $url => $data) {

            $success = false;
            $info = 'Unknown error';

            try {
                $response = $httpClient->request('PATCH', $url, [
                    'headers' => [
                        'Content-Type' => 'application/json',
                    ],
                    'body' => $data,
                ]);

                if (204 === $response->getStatusCode()) {
                    $success = true;
                    $info = null;
                }

            } catch (TransportExceptionInterface $exception) {
                $success = false;
                $info = $exception->getMessage();
                $io->warning($exception->getMessage());
            }

            foreach ($data as $row) {
                /* @var $request ClassificationRequest */
                $request = $origin[$url][$row['id']];
                if ($success) {
                    ++$sent;
                    $request->setSentAt(new \DateTime());
                    $request->setInfo(null);
                } else {
                    $request->setStatus(ClassificationRequest::STATUS_FAILED);
                    $request->setSentAt(null);
                    $request->setInfo($info);
                }
                $this->requestRepository->saveBatch($origin[$url]);exit;
            }
        }

        return $sent;
    }
}

<?php

namespace Adshares\Adclassify\Security\Authentication\Provider;

use Adshares\Adclassify\Repository\ApiKeyRepository;
use Adshares\Adclassify\Security\Authentication\Token\WsseUserToken;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Security\Core\Authentication\Provider\AuthenticationProviderInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class WsseProvider implements AuthenticationProviderInterface
{
    const NONCE_LIFETIME = 30;

    /**
     * @var ApiKeyRepository
     */
    private $apiKeyRepository;

    /**
     * @var CacheItemPoolInterface
     */
    private $cachePool;

    protected $logger;

    public function __construct(
        ApiKeyRepository $apiKeyRepository,
        CacheItemPoolInterface $cachePool,
        LoggerInterface $logger = null
    ) {
        if ($logger === null) {
            $logger = new NullLogger();
        }
        $this->apiKeyRepository = $apiKeyRepository;
        $this->cachePool = $cachePool;
        $this->logger = $logger;
    }

    public function authenticate(TokenInterface $token): TokenInterface
    {
        /* @var $token WsseUserToken */
        $apiKey = $this->apiKeyRepository->findByName($token->getUsername());
        if ($apiKey) {
            $this->logger->debug(sprintf('WSSE: API key %s #%d', $apiKey->getName(), $apiKey->getId()));
        } else {
            $this->logger->debug(sprintf('WSSE: Cannot find API key %s', $token->getUsername()));
        }

        if ($apiKey && $this->validateDigest(
                $token->getDigest(),
                $token->getNonce(),
                $token->getCreated(),
                $apiKey->getSecret()
            )) {
            $this->logger->debug(sprintf('WSSE: digest valid for %s', $apiKey->getUser()->getEmail()));
            $authenticatedToken = new WsseUserToken($apiKey->getUser()->getRoles());
            $authenticatedToken->setUser($apiKey->getUser());

            return $authenticatedToken;
        }

        throw new AuthenticationException('The WSSE authentication failed.');
    }

    protected function validateDigest(string $digest, string $nonce, string $created, string $secret): bool
    {
        // Check created time is not in the future
        if (strtotime($created) > time()) {
            return false;
        }

        // Expire timestamp after 5 minutes
        if (time() - strtotime($created) > self::NONCE_LIFETIME) {
            $this->logger->debug(sprintf('WSSE: Expire timestamp after 5 minutes (%s)', $created));
            return false;
        }

        // Try to fetch the cache item from pool
        try {
            $cacheItem = $this->cachePool->getItem(md5($nonce));
        } catch (InvalidArgumentException $exception) {
            $this->logger->debug(sprintf('WSSE: Invalid nonce (%s)', $nonce));
            throw new AuthenticationException('Invalid nonce');
        }

        // Validate that the nonce is *not* in cache
        // if it is, this could be a replay attack
        if ($cacheItem->isHit()) {
            $this->logger->debug(sprintf('WSSE: Previously used nonce detected (%s)', $nonce));
            throw new AuthenticationException('Previously used nonce detected');
        }

        // Store the item in cache for 5 minutes
        $cacheItem->set(null)->expiresAfter(self::NONCE_LIFETIME);
        $this->cachePool->save($cacheItem);

        // Validate Secret
        $expected = base64_encode(hash('sha256', base64_decode($nonce) . $created . $secret, true));
        $this->logger->debug(sprintf('WSSE: hash expected: %s', $expected));

        return hash_equals($expected, $digest);
    }

    public function supports(TokenInterface $token): bool
    {
        return $token instanceof WsseUserToken;
    }
}

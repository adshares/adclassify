<?php

namespace Adshares\Adclassify\Security\Authentication\Provider;

use Adshares\Adclassify\Repository\ApiKeyRepository;
use Adshares\Adclassify\Security\Authentication\Token\WsseUserToken;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\InvalidArgumentException;
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

    public function __construct(ApiKeyRepository $apiKeyRepository, CacheItemPoolInterface $cachePool)
    {
        $this->apiKeyRepository = $apiKeyRepository;
        $this->cachePool = $cachePool;
    }

    public function authenticate(TokenInterface $token): TokenInterface
    {
        /* @var $token WsseUserToken */
        $apiKey = $this->apiKeyRepository->findByName($token->getUsername());

        if ($apiKey && $this->validateDigest(
            $token->getDigest(),
            $token->getNonce(),
            $token->getCreated(),
            $apiKey->getSecret()
        )) {
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
            return false;
        }

        // Try to fetch the cache item from pool
        try {
            $cacheItem = $this->cachePool->getItem(md5($nonce));
        } catch (InvalidArgumentException $exception) {
            throw new AuthenticationException('Invalid nonce');
        }

        // Validate that the nonce is *not* in cache
        // if it is, this could be a replay attack
        if ($cacheItem->isHit()) {
            throw new AuthenticationException('Previously used nonce detected');
        }

        // Store the item in cache for 5 minutes
        $cacheItem->set(null)->expiresAfter(self::NONCE_LIFETIME);
        $this->cachePool->save($cacheItem);

        // Validate Secret
        $expected = base64_encode(hash('sha256', base64_decode($nonce) . $created . $secret, true));

        return hash_equals($expected, $digest);
    }

    public function supports(TokenInterface $token): bool
    {
        return $token instanceof WsseUserToken;
    }
}

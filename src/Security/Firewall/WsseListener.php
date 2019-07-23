<?php

namespace Adshares\Adclassify\Security\Firewall;

use Adshares\Adclassify\Security\Authentication\Token\WsseUserToken;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class WsseListener
{
    /**
     * @var TokenStorageInterface
     */
    protected $tokenStorage;

    /**
     * @var AuthenticationManagerInterface
     */
    protected $authenticationManager;

    public function __construct(
        TokenStorageInterface $tokenStorage,
        AuthenticationManagerInterface $authenticationManager
    ) {
        $this->tokenStorage = $tokenStorage;
        $this->authenticationManager = $authenticationManager;
    }

    public function __invoke(RequestEvent $event): void
    {
        $request = $event->getRequest();

        // phpcs:ignore Generic.Files.LineLength.TooLong
        $wsseRegex = '/UsernameToken Username="(?P<username>[^"]+)", PasswordDigest="(?P<digest>[^"]+)", Nonce="(?P<nonce>[a-zA-Z0-9+\/]+={0,2})", Created="(?P<created>[^"]+)"/';
        if (!$request->headers->has('x-wsse') || 1 !== preg_match(
            $wsseRegex,
            $request->headers->get('x-wsse'),
            $matches
        )) {
            return;
        }

        $token = new WsseUserToken();
        $token->setUser($matches['username']);
        $token->setDigest($matches['digest']);
        $token->setNonce($matches['nonce']);
        $token->setCreated($matches['created']);

        try {
            $authToken = $this->authenticationManager->authenticate($token);
            $this->tokenStorage->setToken($authToken);
        } catch (AuthenticationException $failed) {
            $token = $this->tokenStorage->getToken();
            if ($token instanceof WsseUserToken) {
                $this->tokenStorage->setToken(null);
            }
        }
    }
}

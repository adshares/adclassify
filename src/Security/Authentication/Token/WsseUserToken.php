<?php

namespace Adshares\Adclassify\Security\Authentication\Token;

use Symfony\Component\Security\Core\Authentication\Token\AbstractToken;

final class WsseUserToken extends AbstractToken
{
    protected string $created;
    protected string $digest;
    protected string $nonce;

    public function __construct(array $roles = [])
    {
        parent::__construct($roles);

        // If the user has roles, consider it authenticated
        $this->setAuthenticated(count($roles) > 0);
    }

    public function getCreated(): string
    {
        return $this->created;
    }

    public function setCreated(string $created): void
    {
        $this->created = $created;
    }

    public function getDigest(): string
    {
        return $this->digest;
    }

    public function setDigest(string $digest): void
    {
        $this->digest = $digest;
    }

    public function getNonce(): string
    {
        return $this->nonce;
    }

    public function setNonce(string $nonce): void
    {
        $this->nonce = $nonce;
    }

    public function getCredentials(): string
    {
        return '';
    }
}

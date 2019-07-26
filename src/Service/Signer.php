<?php

namespace Adshares\Adclassify\Service;

use Adshares\Adclassify\Entity\Classification;

class Signer
{
    private $secretKey;

    public function __construct(string $secretKey)
    {
        $this->secretKey = $secretKey;
    }

    public function signClassification(Classification $classification): string
    {
        $message = $this->createDataMessage($classification->getChecksum(), $classification->getKeywords());

        $key_pair = sodium_crypto_sign_seed_keypair(hex2bin($this->secretKey));
        $key_secret = sodium_crypto_sign_secretkey($key_pair);

        return bin2hex(sodium_crypto_sign_detached($message, $key_secret));
    }

    private function createDataMessage(string $checksum, array $data): string
    {
        ksort($data);

        return hash('sha256', $checksum . json_encode($data));
    }
}

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

    public function checkContent(string $content, string $checksum): bool
    {
        return true;
    }

    private function createDataMessage(string $checksum, array $data): string
    {
        $array = $data;
        self::sort($array);

        return hash('sha256', $checksum . json_encode($array));
    }

    private static function sort(array &$array): void
    {
        foreach ($array as $key => &$value) {
            if (is_array($value)) {
                self::sort($value);
            }
        }

        if (!empty($array) && self::isAssoc($array)) {
            ksort($array);
        } else {
            sort($array);
        }
    }

    private static function isAssoc(array $arr): bool
    {
        return array_keys($arr) !== range(0, count($arr) - 1);
    }
}

<?php

namespace Adshares\Adclassify\Service;

use Adshares\Adclassify\Entity\Ad;

class Signer
{
    private $secretKey;

    public function __construct(string $secretKey)
    {
        $this->secretKey = $secretKey;
    }

    public function signClassification(Ad $ad, int $timestamp): string
    {
        $message = $this->createDataMessage($ad->getChecksum(), $timestamp, $ad->getKeywords());

        $key_pair = sodium_crypto_sign_seed_keypair(hex2bin($this->secretKey));
        $key_secret = sodium_crypto_sign_secretkey($key_pair);

        return bin2hex(sodium_crypto_sign_detached($message, $key_secret));
    }

    public function checkContent(string $content, string $checksum): bool
    {
        return sha1($content, true) === $checksum;
    }

    private function createDataMessage(string $checksum, int $timestamp, array $data): string
    {
        $array = $data;
        self::sort($array);

        return hash('sha256', $checksum . $timestamp . json_encode($array));
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

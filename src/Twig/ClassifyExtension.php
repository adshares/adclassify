<?php

namespace Adshares\Adclassify\Twig;

use Adshares\Adclassify\Entity\Request;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class ClassifyExtension extends AbstractExtension
{
    public function getFilters()
    {
        return [
            new TwigFilter('hex', [$this, 'formatHexData']),
            new TwigFilter('requestStatus', [$this, 'formatRequestStatus']),
            new TwigFilter('callbackStatus', [$this, 'formatCallbackStatus']),
            new TwigFilter('keywords', [$this, 'formatKeywords']),
        ];
    }

    public function formatHexData($data, bool $isBinary = true, bool $add0x = true, bool $uppercase = true)
    {
        $hex = $isBinary ? bin2hex($data) : (int)dechex($data);
        if ($uppercase) {
            $hex = strtoupper($hex);
        }

        return ($add0x ? '0x' : '') . $hex;
    }

    public static function formatRequestStatus(int $status)
    {
        switch ($status) {
            case Request::STATUS_PROCESSED:
                return 'Processed';
            case Request::STATUS_NEW:
                return 'New';
            case Request::STATUS_PENDING:
                return 'Pending';
            case Request::STATUS_REJECTED:
                return 'Rejected';
            case Request::STATUS_CANCELED:
                return 'Canceled';
            default:
                return 'Unknown';
        }
    }

    public static function formatCallbackStatus(?int $status)
    {
        if ($status === null) {
            return 'N/A';
        }

        switch ($status) {
            case Request::CALLBACK_SUCCESS:
                return 'Success';
            case Request::CALLBACK_PENDING:
                return 'Pending';
            case Request::CALLBACK_FAILED:
                return 'Failed';
            default:
                return 'Unknown';
        }
    }

    public static function formatKeywords(array $keywords)
    {
        $dicts = [];
        foreach ($keywords as $name => $dict) {
            $dicts[] =  $name . ': ' .  implode(', ', $dict);
        }

        return empty($dicts) ? '-' : implode(' | ', $dicts);
    }
}

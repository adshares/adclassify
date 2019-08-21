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

    public static function formatRequestStatus(Request $request, bool $extraData = false, bool $short = false)
    {

        $labels = [
            Request::STATUS_PROCESSED => ['Processed', '✓'],
            Request::STATUS_NEW => ['New', '⋯'],
            Request::STATUS_PENDING => ['Pending', '⋯'],
            Request::STATUS_REJECTED => ['Rejected', '⨉'],
            Request::STATUS_CANCELED => ['Canceled', '⨉'],
        ];

        $label = $short ? '?' : 'Unknown';
        if (array_key_exists($request->getStatus(), $labels)) {
            $label = $labels[$request->getStatus()][(int)$short];
        }

        if ($extraData && $request->getInfo()) {
            $label .= ' | ' . $request->getInfo();
        }

        return $label;
    }

    public static function formatCallbackStatus(Request $request, bool $extraData = false, bool $short = false)
    {
        $labels = [
            Request::CALLBACK_SUCCESS => ['Success', '✓'],
            Request::CALLBACK_PENDING => ['Pending', '⋯'],
            Request::CALLBACK_FAILED => ['Failed', '⨉'],
        ];

        $label = $short ? '?' : 'Unknown';
        if ($request->getCallbackStatus() === null) {
            $label = $short ? '' : 'N/A';
        } elseif (array_key_exists($request->getCallbackStatus(), $labels)) {
            $label = $labels[$request->getCallbackStatus()][(int)$short];
        }

        if ($extraData && $request->getSentAt()) {
            $label .= ' | ' . $request->getSentAt()->format('Y-m-d H:m:s');
        }

        return $label;
    }

    public static function formatKeywords(?array $keywords)
    {
        $dicts = [];
        foreach ((array)$keywords as $name => $dict) {
            $dicts[] = $name . ': ' . implode(', ', $dict);
        }

        return empty($dicts) ? '-' : implode(' | ', $dicts);
    }
}

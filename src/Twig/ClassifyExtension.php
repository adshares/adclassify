<?php

namespace Adshares\Adclassify\Twig;

use Adshares\Adclassify\Entity\Ad;
use Adshares\Adclassify\Entity\Request;
use finfo;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class ClassifyExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('hex', [$this, 'formatHexData']),
            new TwigFilter('requestStatus', [$this, 'formatRequestStatus']),
            new TwigFilter('callbackStatus', [$this, 'formatCallbackStatus']),
            new TwigFilter('keywords', [$this, 'formatKeywords']),
            new TwigFilter('image64', [$this, 'formatImage64']),
            new TwigFilter('video64', [$this, 'formatVideo64']),
        ];
    }

    public function formatHexData($data, bool $isBinary = true, bool $add0x = true, bool $uppercase = true): string
    {
        $hex = $isBinary ? bin2hex($data) : (int)dechex($data);
        if ($uppercase) {
            $hex = strtoupper($hex);
        }

        return ($add0x ? '0x' : '') . $hex;
    }

    public static function formatRequestStatus(Request $request, bool $extraData = false, bool $short = false): string
    {

        $labels = [
            Request::STATUS_PROCESSED => ['Processed', '✓'],
            Request::STATUS_NEW => ['New', '⋯'],
            Request::STATUS_PENDING => ['Pending', '⋯'],
            Request::STATUS_FAILED => ['Failed', '⨉'],
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

    public static function formatCallbackStatus(Request $request, bool $extraData = false, bool $short = false): string
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

    public static function formatKeywords(?array $keywords): string
    {
        $dicts = [];
        foreach ((array)$keywords as $name => $dict) {
            $dicts[] = $name . ': ' . implode(', ', $dict);
        }

        return empty($dicts) ? '-' : implode(' | ', $dicts);
    }

    public function formatImage64($raw, $inline = true): string
    {
        if ($raw instanceof Request) {
            $raw = $raw->getAd()->getContent();
        } elseif ($raw instanceof Ad) {
            $raw = $raw->getContent();
        }

        return $inline ? sprintf('data:image;base64,%s', base64_encode($raw)) : base64_encode($raw);
    }

    public function formatVideo64($raw, $inline = true): string
    {
        if ($raw instanceof Request) {
            $raw = $raw->getAd()->getContent();
        } elseif ($raw instanceof Ad) {
            $raw = $raw->getContent();
        }

        $mimeType = (new finfo(FILEINFO_MIME_TYPE))->buffer($raw);

        return $inline ? sprintf('data:%s;base64,%s', $mimeType, base64_encode($raw)) : base64_encode($raw);
    }
}

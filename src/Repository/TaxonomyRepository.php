<?php

namespace Adshares\Adclassify\Repository;

class TaxonomyRepository
{
    public const CATEGORY = 'category';
    public const QUALITY = 'quality';
    public const CATEGORY_SAFE = 'safe';

    public function getTaxonomy(): array
    {
        return [
            [
                'key' => self::CATEGORY,
                'label' => 'Category',
                'values' => $this->getCatgories(),
            ],
            [
                'key' => self::QUALITY,
                'label' => 'Quality',
                'values' => $this->getQualityLevels(),
            ],
        ];
    }

    public function getCatgories(): array
    {
        return [
            [
                'key' => 'adult',
                'label' => 'Adult',
                'description' => 'NSFW, nudity, pornography'
            ],
            [
                'key' => 'annoying',
                'label' => 'Annoying',
                'description' => 'Sounds, flashing, disturbing'
            ],
            [
                'key' => 'crypto',
                'label' => 'Crypto',
                'description' => 'Cryptocurrencies, exchanges, wallets'
            ],
            [
                'key' => 'drugs',
                'label' => 'Supplements',
                'description' => 'Medicines, dietary supplement'
            ],
            [
                'key' => 'gambling',
                'label' => 'Gambling',
                'description' => 'Sports betting, casinos, lottery'
            ],
            [
                'key' => 'investment',
                'label' => 'Investment',
                'description' => 'HYIPs, ICO/IEO, crowdfunding'
            ],
            [
                'key' => 'malware',
                'label' => 'Software',
                'description' => 'Software download, extensions'
            ],
            [
                'key' => 'quality',
                'label' => 'Quality Ads',
                'description' => 'Safe and good quality ads'
            ],
            [
                'key' => self::CATEGORY_SAFE,
                'label' => 'Mainstream',
                'description' => 'Safe content'
            ],
        ];
    }

    public function getQualityLevels(): array
    {
        return [
            [
                'key' => 'high',
                'label' => 'Premium',
                'description' => 'Premium quality campaign'
            ],
            [
                'key' => 'medium',
                'label' => 'Regular',
                'description' => 'Regular campaign'
            ],
            [
                'key' => 'low',
                'label' => 'Low quality',
                'description' => 'Low quality campaign'
            ],
        ];
    }
}

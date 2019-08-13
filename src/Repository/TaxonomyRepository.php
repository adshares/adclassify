<?php

namespace Adshares\Adclassify\Repository;

class TaxonomyRepository
{
    public function getTaxonomy(): array
    {
        return [
            [
                'key' => 'category',
                'label' => 'Category',
                'values' => $this->getCatgories(),
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
                'label' => 'Drugs',
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
                'label' => 'Malware',
                'description' => 'Software download, extensions'
            ],
            ['key' => 'safe', 'label' => 'Safe'],
        ];
    }
}

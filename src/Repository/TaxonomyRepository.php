<?php


namespace Adshares\Adclassify\Repository;


class TaxonomyRepository
{
    public function getTaxonomy(): array
    {
        return [
            [
                'label' => 'Category',
                'key' => 'category',
                'type' => 'dict',
                'data' => self::sanitazeData($this->getCatgories()),
            ],
        ];
    }

    public function getCatgories(): array
    {
        return [
            'safe' => 'Safe',
            'ntsf' => 'NTSF',
            'gambling' => 'Gambling',
            'crypto' => 'Crypto',
        ];
    }

    private static function sanitazeData(array $data): array
    {
        $result = [];
        foreach ($data as $key => $label) {
            $result[] = [
                'key' => $key,
                'label' => $label,
            ];
        }

        return $result;
    }
}
<?php

namespace Adshares\Adclassify\Controller;

use Adshares\Adclassify\Repository\TaxonomyRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ApiController extends AbstractController
{

    public function taxonomy(TaxonomyRepository $repository): Response
    {
        $taxonomy = [
            'meta' => [
                'name' => getenv('TAXONOMY_NAME'),
                'version' => getenv('TAXONOMY_VERSION'),
            ],
            'data' => $repository->getTaxonomy(),
        ];

        return new JsonResponse($taxonomy);
    }
}

<?php

namespace Adshares\Adclassify\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class AppController extends AbstractController
{

    public function index(): Response
    {
        return $this->render('app/index.html.twig', []);
    }
}

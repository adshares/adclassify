<?php

namespace Adshares\Adclassify\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class AdminController extends AbstractController
{
    public function index(): Response
    {
        return $this->render('admin/index.html.twig', []);
    }
}

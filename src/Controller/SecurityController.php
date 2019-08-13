<?php

namespace Adshares\Adclassify\Controller;

use Adshares\Adclassify\Form\LoginFormType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class SecurityController extends AbstractController
{
    public function login(): Response
    {
        $form = $this->createForm(LoginFormType::class);

        return $this->render('security/login.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    public function account(): Response
    {
        return $this->render('security/account.html.twig', []);
    }
}

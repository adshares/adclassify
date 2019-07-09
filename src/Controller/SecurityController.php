<?php

namespace Adshares\Adclassify\Controller;

use Adshares\Adclassify\Entity\User;
use Adshares\Adclassify\Form\LoginFormType;
use Adshares\Adclassify\Security\LoginFormAuthenticator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Guard\GuardAuthenticatorHandler;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{

    public function login(Request $request, AuthenticationUtils $authenticationUtils)
    {
        $form = $this->createForm(LoginFormType::class);

        return $this->render('security/login.html.twig', [
            'form' => $form->createView(),
        ]);
    }


    //<form action="{{ path('form_login_check') }}" method="post">

//    public function login(
//        Request $request,
//        UserPasswordEncoderInterface $passwordEncoder,
//        GuardAuthenticatorHandler $guardHandler,
//        LoginFormAuthenticator $authenticator
//    ): Response {
//        $form = $this->createForm(LoginFormType::class);
//        $form->handleRequest($request);
//
//        if ($form->isSubmitted() && $form->isValid()) {
//
//
//
////            dump($form->getData());exit;
//
////            // encode the plain password
////            $user->setPassword(
//                $passwordEncoder->encodePassword(
//                    $user,
//                    $form->get('plainPassword')->getData()
//                )
////            );
////
////            $entityManager = $this->getDoctrine()->getManager();
////            $entityManager->persist($user);
////            $entityManager->flush();
////
////            // do anything else you need here, like send an email
////
////            return $guardHandler->authenticateUserAndHandleSuccess(
////                $user,
////                $request,
////                $authenticator,
////                'main' // firewall name in security.yaml
////            );
//        }
//
//        return $this->render('security/login.html.twig', [
//            'form' => $form->createView(),
//        ]);
//
////        // get the login error if there is one
////        $error = $authenticationUtils->getLastAuthenticationError();
////        // last username entered by the user
////        $lastUsername = $authenticationUtils->getLastUsername();
////
////        return $this->render('security/login.html.twig', [
////            'last_username' => $lastUsername,
////            'error' => $error,
////        ]);
//    }
}

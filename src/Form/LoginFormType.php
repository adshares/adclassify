<?php

namespace Adshares\Adclassify\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Security;

class LoginFormType extends AbstractType
{
    private $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('username', EmailType::class, [
                'attr' => [
                    'placeholder' => 'Email address'
                ],
            ])
            ->add('password', PasswordType::class, [
                'attr' => [
                    'placeholder' => 'Password'
                ],
            ]);

        $request = $this->requestStack->getCurrentRequest();

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($request) {
            dump($request);exit;
            if ($request->attributes->has(Security::AUTHENTICATION_ERROR)) {
                $error = $request->attributes->get(Security::AUTHENTICATION_ERROR);
            } else {
                $error = $request->getSession()->get(Security::AUTHENTICATION_ERROR);
            }
            if ($error) {
                $event->getForm()->addError(new FormError($error->getMessage()));
            }
            $event->setData(array_replace((array)$event->getData(), array(
                'username' => $request->getSession()->get(Security::LAST_USERNAME),
            )));
        });
    }

//    public function configureOptions(OptionsResolver $resolver)
//    {
//        /* Note: the form's csrf_token_id must correspond to that for the form login
//         * listener in order for the CSRF token to validate successfully.
//         */
//        $resolver->setDefaults([
//            'csrf_token_id' => 'authenticate',
//        ]);
//    }
}

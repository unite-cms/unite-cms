<?php

namespace UniteCMS\CoreBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

class ContentDeleteFormType extends AbstractType
{
    /**
     * @var TokenStorage $tokenStorage
     */
    private $tokenStorage;

    public function __construct(TokenStorage $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        if ($this->tokenStorage->getToken() && $this->tokenStorage->getToken()->getProviderKey() == "api") {
            $resolver->setDefault('csrf_protection', false);
        }
    }
}

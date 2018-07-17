<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 10.05.18
 * Time: 14:36
 */

namespace UniteCMS\CoreBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use UniteCMS\CoreBundle\Form\Model\InvitationRegistrationModel;


class InvitationRegistrationType extends AbstractType
{

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name',TextType::class, ['label' => $options['label_prefix'].'.name.label', 'required' => true, 'attr' => ['autocomplete' => 'off']])
            ->add('email', EmailType::class, ['label' => $options['label_prefix'].'.email.label', 'disabled' => true ])
            ->add('password', RepeatedType::class, [
                'type' => PasswordType::class,
                'invalid_message' => 'passwords_must_match',
                'required' => true,
                'first_options' => array('label' => $options['label_prefix'].'.password.label'),
                'second_options' => array('label' => $options['label_prefix'].'.password_repeat.label'),
                'attr' => ['autocomplete' => 'off'],
            ])
            ->add('submit', SubmitType::class, ['label' => $options['label_prefix'].'.submit']);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setDefaults([
                'data_class' => InvitationRegistrationModel::class,
                'label_prefix' => 'profile.accept_invitation',
            ]);
    }
}
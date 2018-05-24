<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 24.05.18
 * Time: 11:37
 */

namespace UniteCMS\RegistrationBundle\Form;


use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use UniteCMS\CoreBundle\Form\InvitationRegistrationType;
use UniteCMS\RegistrationBundle\Form\Model\RegistrationModel;

class RegistrationType extends InvitationRegistrationType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);
        $builder->get('email')->setDisabled(false);
        $builder->add('organizationTitle', TextType::class, ['label' => $options['label_prefix'].'.organization_title.label', 'required' => true, 'attr' => ['autocomplete' => 'off']]);
        $builder->add('organizationIdentifier', TextType::class, ['label' => $options['label_prefix'].'.organization_identifier.label', 'required' => true, 'attr' => ['autocomplete' => 'off']]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
        $resolver
            ->setDefaults([
                'data_class' => RegistrationModel::class,
                'label_prefix' => 'registration.registration',
            ]);
    }
}
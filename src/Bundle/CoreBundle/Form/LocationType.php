<?php
/**
 * Created by PhpStorm.
 * User: stefankamsker
 * Date: 17.09.18
 * Time: 14:19
 */

namespace UniteCMS\CoreBundle\Form;

use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LocationType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('provided_by', HiddenType::class)
            ->add('id', HiddenType::class)
            ->add('category', HiddenType::class, [
                'not_empty' => $options['not_empty'],
                'error_bubbling' => true,
            ])
            ->add('display_name', HiddenType::class)
            ->add('latitude', HiddenType::class)
            ->add('longitude', HiddenType::class)
            ->add('bound_south', HiddenType::class)
            ->add('bound_west', HiddenType::class)
            ->add('bound_north', HiddenType::class)
            ->add('bound_east', HiddenType::class)
            ->add('street_number', HiddenType::class)
            ->add('street_name', HiddenType::class)
            ->add('postal_code', HiddenType::class)
            ->add('locality', HiddenType::class)
            ->add('sub_locality', HiddenType::class)
            ->add('admin_levels', CollectionType::class, [
                'entry_type' => LocationAdminLevelType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'delete_empty' => true,
            ])
            ->add('country_code', HiddenType::class);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefault('error_bubbling', false);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'unite_cms_core_location';
    }
}

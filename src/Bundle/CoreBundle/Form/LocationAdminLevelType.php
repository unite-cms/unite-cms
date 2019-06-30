<?php
/**
 * Created by PhpStorm.
 * User: stefankamsker
 * Date: 17.09.18
 * Time: 14:19
 */

namespace UniteCMS\CoreBundle\Form;

use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\AbstractType;

class LocationAdminLevelType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', HiddenType::class)
            ->add('code', HiddenType::class)
            ->add('level', HiddenType::class);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'unite_cms_core_location_admin_level';
    }

}
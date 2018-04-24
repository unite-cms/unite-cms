<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 09.02.18
 * Time: 15:07
 */

namespace UniteCMS\WysiwygFieldBundle\Form;

use Symfony\Component\OptionsResolver\OptionsResolver;
use UniteCMS\CoreBundle\Form\WebComponentType;

class WysiwygType extends WebComponentType
{
    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'unite_cms_wysiwyg';
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return WebComponentType::class;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
        $resolver->setDefaults(
            [
                'tag' => 'unite-cms-wysiwyg-field',
            ]
        );
    }
}

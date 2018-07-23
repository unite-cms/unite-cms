<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 09.02.18
 * Time: 15:07
 */

namespace UniteCMS\StorageBundle\Form;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use UniteCMS\CoreBundle\Form\WebComponentType;

class StorageFileType extends WebComponentType
{
    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'unite_cms_storage_file';
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return WebComponentType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        parent::buildView($view, $form, $options);
        $view->vars['assets'] = [
            [ 'css' => 'main.css', 'package' => 'UniteCMSStorageBundle' ],
            [ 'js' => 'main.js', 'package' => 'UniteCMSStorageBundle' ],
        ];
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
        $resolver->setDefaults(
            [
                'tag' => 'unite-cms-storage-file-field',
                'file-types' => '*',
            ]
        );
        $resolver->setRequired(['file-types']);
    }
}

<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 09.02.18
 * Time: 15:07
 */

namespace UnitedCMS\StorageBundle\Form;

use Symfony\Component\OptionsResolver\OptionsResolver;
use UnitedCMS\CoreBundle\Form\WebComponentType;

class StorageFileType extends WebComponentType
{
    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix() {
        return 'united_cms_storage_file';
    }

    /**
     * {@inheritdoc}
     */
    public function getParent() {
        return WebComponentType::class;
    }

    public function configureOptions(OptionsResolver $resolver) {
        parent::configureOptions($resolver);
        $resolver->setDefaults([
            'tag' => 'united-cms-storage-file-field',
            'file-types' => '*',
        ]);
        $resolver->setRequired(['file-types']);
    }
}
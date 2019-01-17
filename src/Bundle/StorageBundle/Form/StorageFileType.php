<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 09.02.18
 * Time: 15:07
 */

namespace UniteCMS\StorageBundle\Form;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use UniteCMS\CoreBundle\Form\WebComponentType;

class StorageFileType extends WebComponentType implements DataTransformerInterface
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
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);
        $builder->add('name', HiddenType::class);
        $builder->add('type', HiddenType::class);
        $builder->add('size', HiddenType::class);
        $builder->add('id', HiddenType::class);
        $builder->add('checksum', HiddenType::class);

        $builder->addModelTransformer($this);
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
                'compound' => true,
                'error_bubbling' => false,
                'tag' => 'unite-cms-storage-file-field',
                'file-types' => '*',
                'empty_data' => [
                    'name' => null,
                    'type' => null,
                    'size' => null,
                    'id' => null,
                    'checksum' => null,
                ],
            ]
        );
        $resolver->setRequired(['file-types']);
    }

    /**
     * {@inheritdoc}
     */
    public function transform($value)
    {
        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($value)
    {
        if (empty($value) || empty($value['name']) || empty($value['id'])) {
            return null;
        }

        return $value;
    }
}

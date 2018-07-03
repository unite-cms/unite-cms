<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 09.02.18
 * Time: 15:07
 */

namespace UniteCMS\WysiwygFieldBundle\Form;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use UniteCMS\CoreBundle\Form\WebComponentType;
use voku\helper\AntiXSS;

class WysiwygType extends WebComponentType
{
    /**
     * @var AntiXSS $antiXss
     */
    private $antiXss;

    public function __construct()
    {
        $this->antiXss = new AntiXSS();
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addModelTransformer($this);
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        parent::buildView($view, $form, $options);
        $view->vars['assets'] = [
            [ 'css' => 'main.css', 'package' => 'UniteCMSWysiwygFieldBundle' ],
            [ 'js' => 'main.js', 'package' => 'UniteCMSWysiwygFieldBundle' ],
        ];
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
        $resolver->setDefaults(
            [
                'tag' => 'unite-cms-wysiwyg-field',
                'empty_data' => '',
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function transform($data)
    {
        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($data)
    {
        if(empty($data)) {
            return null;
        }

        return $this->antiXss->xss_clean($data);
    }

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
}

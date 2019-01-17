<?php
/**
 * Created by PhpStorm.
 * User: stefankamsker
 * Date: 17.09.18
 * Time: 14:19
 */

namespace UniteCMS\CoreBundle\Form;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Contracts\Translation\TranslatorInterface;

class LinkType extends AbstractType
{
    /**
     * @var TranslatorInterface $translator
     */
    private $translator;

    /**
     * @var array $url_targets
     */
    private $url_targets;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {

        $resolver->setDefaults([
            'label_prefix' => 'link_type',
            'compound' => true,
            'error_bubbling' => true,
            'title_widget' => false,
            'target_widget' => false,
        ]);

    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $builder->add('url', UrlType::class,
            [
                'label' => $options['label_prefix'].'.url.label',
                'default_protocol' => 'https'
            ]
        );

        if (isset($options['title_widget']) && $options['title_widget'])
        {

            $builder->add('title', TextType::class,
                [
                    'label' => $options['label_prefix'].'.title.label',
                ]
            );

        }

        if (isset($options['target_widget']) && $options['target_widget'])
        {

            $this->url_targets = [
                $this->translator->trans('link_type.choice.self.label') => "_self",
                $this->translator->trans('link_type.choice.blank.label') => "_blank"
            ];

            $builder->add('target', ChoiceType::class,
                [
                    'label' => $options['label_prefix'].'.target.label',
                    'choices' => $this->url_targets,
                    'attr' => ['class' => 'unite-choice-form-icon-toggle']
                ]
            );

        }
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        if(isset($view->vars['label'])) {
            $view->vars['widget_label'] = $view->vars['label'];
            $view->vars['label'] = false;
        }
        parent::buildView($view, $form, $options);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'unite_cms_core_link';
    }

}
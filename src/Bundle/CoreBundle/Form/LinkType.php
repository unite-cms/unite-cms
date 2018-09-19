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
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Translation\TranslatorInterface;

class LinkType extends AbstractType
{
    /**
     * @var Translator $translator
     */
    private $translator;

    /**
     * @var array $url_targets
     */
    private $url_targets;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;

        $this->url_targets = [
            $this->translator->trans('link_type.choice.self.label') => "_self",
            $this->translator->trans('link_type.choice.blank.label') => "_blank"
        ];

    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {

        $resolver->setDefaults([
            'label_prefix' => 'link_type',
            'compound' => true,
            'title_widget' => false,
            'target_widget' => false
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
                'attr' => ['class' => 'link-url'],
                'default_protocol' => 'https'
            ]
        );

        if (isset($options['title_widget']) && $options['title_widget'])
        {

            $builder->add('title', TextType::class,
                [
                    'label' => $options['label_prefix'].'.title.label',
                    'attr' => ['class' => 'link-title']
                ]
            );

        }

        if (isset($options['target_widget']) && $options['target_widget'])
        {

            $builder->add('target', ChoiceType::class,
                [
                    'label' => $options['label_prefix'].'.target.label',
                    'choices' => $this->url_targets,
                    'attr' => ['class' => 'link-target']
                ]
            );

        }

    }

}
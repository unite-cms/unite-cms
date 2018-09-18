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

class LinkType extends AbstractType
{

    const URL_TARGETS = [
        "Open link in the same window" => "_self",
        "Open link in a new window" => "_blank"
    ];

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {

        $resolver->setDefaults(array(
            'compound' => true,
            'title_widget' => false,
            'target_widget' => false
        ));

    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $builder->add('url', UrlType::class,
            [
                'label' => 'link_type.url.label',
                'attr' => ['class' => 'link-url'],
                'default_protocol' => 'https'
            ]
        );

        if (isset($options['title_widget']) && $options['title_widget'])
        {

            $builder->add('title', TextType::class,
                [
                    'label' => 'link_type.title.label',
                    'attr' => ['class' => 'link-title']
                ]
            );

        }

        if (isset($options['target_widget']) && $options['target_widget'])
        {

            $builder->add('target', ChoiceType::class,
                [
                    'label' => 'link_type.target.label',
                    'choices' => self::URL_TARGETS,
                    'attr' => ['class' => 'link-target']
                ]
            );

        }

    }

}
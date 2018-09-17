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
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class LinkType extends AbstractType implements DataTransformerInterface
{

    const URL_TARGETS = [
        "Open the link in the same frame as it was clicked (default)" => "_self",
        "Open the link in a new window or tab" => "_blank",
        "Open the link  in the parent frame" => "_parent",
        "Open the link in the full body of the window" => "_top"
    ];

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {

        $resolver->setDefaults(array(
            'compound' => true
        ));

    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $builder->add(
            'url',
            UrlType::class,
            [
                'attr' => ['class' => 'link-url'],
            ]
        );

        $builder->add(
            'title',
            TextType::class,
            [
                'attr' => ['class' => 'link-title'],
            ]
        );

        $builder->add(
            'target',
            ChoiceType::class,
            [
                'choices' => self::URL_TARGETS,
                'attr' => ['class' => 'link-target'],
            ]
        );


        $builder->addViewTransformer($this);

    }

    /**
     * {@inheritdoc}
     */
    public function transform($data)
    {
        $data = json_decode($data);
        return (array) $data;
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($data)
    {
        if (!is_string($data) && null !== $data) {
            return json_encode($data);
        }

        return $data;
    }

}
<?php

namespace UniteCMS\VariantsFieldBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use UniteCMS\CoreBundle\Field\FieldTypeManager;
use UniteCMS\CoreBundle\Form\ChoiceCardsType;
use UniteCMS\CoreBundle\Form\FieldableFormField;
use UniteCMS\CoreBundle\Form\FieldableFormType;
use UniteCMS\CoreBundle\Form\Model\ChoiceCardOption;
use UniteCMS\VariantsFieldBundle\Model\VariantsField;
use UniteCMS\VariantsFieldBundle\Model\Variants;

class VariantsFormType extends AbstractType implements DataTransformerInterface
{
    /**
     * @var FieldTypeManager $fieldTypeManager
     */
    private $fieldTypeManager;

    public function __construct(FieldTypeManager $fieldTypeManager)
    {
        $this->fieldTypeManager = $fieldTypeManager;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /**
         * @var Variants $variants
         */
        $variants = $options['variants'];

        // Add choices type to select type.
        $builder->add('type', ChoiceCardsType::class, [
            'required' => false,
            'label' => false,
            'choices' => array_map(function($variant){
                return new ChoiceCardOption($variant['identifier'], $variant['title'], $variant['description'] ?? '', $variant['icon'] ?? '');
            }, $variants->getVariantsMetadata()),
            'compact' => true,
        ]);

        // Add fieldable form types for each type.
        foreach($variants->getVariantsMetadata() as $variant) {
            $builder->add($variant['identifier'], FieldableFormType::class, [
                'label' => false,
                'attr' => [
                    'data-variant-title' => $variant['title'],
                ],
                'fields' => array_map(function(VariantsField $variant){
                    return new FieldableFormField($this->fieldTypeManager->getFieldType($variant->getType()), $variant);
                }, $variants->getFieldsForVariant($variant['identifier'])),
            ]);
        }

        // Remove all unused variants.
        $builder->addModelTransformer($this);

        // Only enable the variant we have selected. This prevent future validation of all other variants.
        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $selected_type = empty($event->getData()['type']) ? null : $event->getData()['type'];
            foreach($event->getForm()->getConfig()->getOption('variants')->getVariantsMetadata() as $variant) {
                if($variant['identifier'] !== $selected_type) {
                    if($event->getForm()->has($variant['identifier'])) {
                        $childForm = $event->getForm()->get($variant['identifier']);
                        $options = $childForm->getConfig()->getOptions();
                        $options['disabled'] = true;
                        $event->getForm()->remove($variant['identifier']);
                        $event->getForm()->add($variant['identifier'], FieldableFormType::class, $options);
                    }
                }
            }
        });
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        parent::buildView($view, $form, $options);

        // In order to be able to have required child elements (see Symfony\Component\Form\Form::isRequired()), we
        // set the variant form type to required. Here we undo this to avoid a * in the label. At this point,
        // however the children are already built, so any required fields are already marked as required.
        $view->vars['required'] = false;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired('variants');
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'unite_cms_variants';
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
        // If type is not set, variants field is empty.
        if(empty($value['type'])) {
            return null;
        }

        // If type is set, but there is no content for this variant, return an empty array for the variant.
        if(empty($value[$value['type']])) {
            return [
                'type' => $value['type'],
                $value['type'] => [],
            ];
        }

        // Return type but only content for the selected variant.
        return [
            'type' => $value['type'],
            $value['type'] => $value[$value['type']],
        ];
    }
}

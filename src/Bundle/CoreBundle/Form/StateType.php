<?php
/**
 * Created by PhpStorm.
 * User: stefan.kamsker
 * Date: 23.09.18
 * Time: 20:02
 */

namespace UniteCMS\CoreBundle\Form;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Translation\TranslatorInterface;
use UniteCMS\CoreBundle\Model\State;

class StateType extends AbstractType implements DataTransformerInterface
{

    private $settings;

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->settings = $options['settings'];

        $builder->add('transition', ChoiceType::class,
            [
                'label' => $options['label_prefix'].'.field.label',
                'choices' => $this->getChoices($this->settings['transitions']),
                'placeholder' => $options['label_prefix'].'.field.placeholder',
                'constraints' => [
                    new Callback(['callback' => [$this, 'validTransitions']]),
                ],
            ]
        );

        $builder->add('state', HiddenType::class,
            [
                'label' => 'dere',
                'constraints' => [
                    new Callback(['callback' => [$this, 'validPlaces']]),
                ],
            ]
        );

        $builder->addModelTransformer($this);

    }

    public function validTransitions($value, ExecutionContextInterface $context, $payload)
    {
        // if new content, break
        if (!$value) {
            return;
        }

        $current_state = $context->getObject()->getParent()->getData();
        $transition_to = $this->settings['transitions'][$value]['to'];

        // at this point, transition is already made, so we simply compare the values
        if ($current_state && $current_state != $transition_to)
        {
            $context->buildViolation('workflow_transition_not_allowed')->atPath('transition')->addViolation();
        }
    }

    public function validPlaces($value, ExecutionContextInterface $context, $payload)
    {
        if (!in_array($value, array_keys($this->settings['places'])))
        {
            $context->buildViolation('workflow_invalid_place')
                ->atPath('[state][transition]')
                ->addViolation();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        if (isset($view->vars['label']))
        {
            $view->vars['widget_label'] = $view->vars['label'];
            $view->vars['label'] = false;
        }

        if ($view->vars['value']['state']) {
            $view->vars['current_state'] = 'Current State: '.$this->settings['places'][$view->vars['value']['state']]['label'];
        }

        parent::buildView($view, $form, $options);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'label_prefix' => 'state',
            'settings' => [],
            'compound' => true
        ]);

    }

     /**
     * @param array $transitions
     *
     * @return array
     */
    private function getChoices(array $transitions) : array
    {
        $choices = [];

        foreach ($transitions as $transition_key => $transition) {
            $choices[$transition['label']] = $transition_key;
        }

        return $choices;
    }

    /**
     * {@inheritdoc}
     */
    public function transform($value)
    {
        $new_values = [];
        
        $new_values['state'] = $value;
        
        // transition can be alway null
        $new_values['transition'] = null;

        // if no state set (new content), take initial place
        if (empty($value))
        {
            $new_values['state'] = $this->settings['initial_place'];
        }

        return $new_values;
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($value)
    {   

        $state = new State($value['state']);
        $state->setSettings($this->settings);

        $new_value = $value['state'];

        // if transition is given, set value only if transition is posssible
        if (isset($value['transition'])
            && $value['transition']
            && $state->canTransist($value['transition']))
        {
            $new_value = $this->settings['transitions'][$value['transition']]['to'];
        }

        return $new_value;
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'state';
    }
}
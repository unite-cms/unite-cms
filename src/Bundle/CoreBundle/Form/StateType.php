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
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;
use UniteCMS\CoreBundle\Model\State;

class StateType extends AbstractType implements DataTransformerInterface
{

    private $settings;

    /**
     * @var TranslatorInterface $translator
     */
    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->setSettings($options['settings']);

        $builder->add('state', HiddenType::class,
            [
                'label' => '',
                'constraints' => [
                    new Callback(['callback' => [$this, 'validPlaces']]),
                ],
            ]
        );

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($options) {

            $state = $event->getData();
            $form = $event->getForm();

            $formOptions = array(
                'label' => $options['label_prefix'].'.field.label',
                'attr' => array('class' => 'state-transition'),
                'choices' => $this->getChoices(
                    $this->settings['transitions']
                ),
                'placeholder' => $options['label_prefix'].'.field.placeholder',
                'constraints' => [
                    new Callback(
                        ['callback' => [$this, 'validTransitions']]
                    ),
                ],
                'choice_attr' => function($key, $val, $index) {

                    // disable all options for new content
                    return [
                        'class' => $this->getPlaceCategory($key),
                        'disabled' => 'disabled'
                    ];

                }
            );

            // existing state, set attributes and disabled
            if ($state)
            {
                $formOptions['choice_attr'] = function($key, $val, $index) use ($state) {

                    $ret = [
                        'class' => $this->getPlaceCategory($key)
                    ];

                    if ($this->canTransist($state, $key))
                    {
                        $ret['disabled'] = 'disabled';
                    }

                    return $ret;
                };
            }

            $form->add('transition', ChoiceType::class, $formOptions);

        });

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
            $view->vars['current_state'] = $this->translator->trans('state.field.current_state');
            $view->vars['current_state'] .= $this->settings['places'][$view->vars['value']['state']]['label'];
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
     * Check if can transist from current state
     *
     * @param string $current_state
     * @param string $transition_to
     *
     * @return bool
     */
    public function canTransist(string $current_state, string $transition_to) : bool
    {
        // transitions not set
        if (!isset($this->settings['transitions'][$transition_to]))
        {
            return FALSE;
        }

        $transition = $this->settings['transitions'][$transition_to];

        if (in_array($current_state, $transition['from']))
        {
            return TRUE;
        }

        return FALSE;
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
     * @param string $transition_key
     *
     * @return string
     */
    private function getPlaceCategory(string $transition_key) : string
    {
        $place = $this->settings['transitions'][$transition_key]['to'];

        if (isset($this->settings['places'][$place]['category'])) {
            return $this->settings['places'][$place]['category'];
        }

        return "";
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
        $new_value = $value['state'];

        // if transition is given, set value only if transition is posssible
        if (isset($value['transition'])
            && $value['transition'])
        {
            $transition_to = $this->settings['transitions'][$value['transition']]['to'];

            if ($this->canTransist($value['state'], $value['transition']))
            {
                $new_value = $transition_to;
            }
        }

        return $new_value;
    }

    /**
     * @param array $settings
     *
     * @return StateType
     */
    public function setSettings(array $settings)
    {
        $this->settings = $settings;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'state';
    }
}
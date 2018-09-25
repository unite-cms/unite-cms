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
                'empty_data' => $this->settings['initial_place'],
                'constraints' => [
                    new Callback(['callback' => [$this, 'validPlaces']]),
                ],
            ]
        );

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($options) {

            $state = $event->getData();
            $form = $event->getForm();

            // new content, no state data
            if (!$state) {
                $state = $this->settings['initial_place'];
            }

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
                'choice_attr' => function($key, $val, $index) use ($state) {

                    $ret = [
                        'data-category' => $this->getPlaceCategory($key)
                    ];

                    if (!$this->canTransist($state, $key))
                    {
                        $ret['disabled'] = 'disabled';
                    }

                    return $ret;

                }
            );

            $form->add('transition', ChoiceType::class, $formOptions);

        });

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {

            $data = $event->getData();

            // new content, no state data
            if (!isset($data['state'])) {
                $data['state'] = $this->settings['initial_place'];
            }

            // if transition is given, set value only if transition is posssible
            if (isset($data['transition'])
                && $data['transition']
                && isset($this->settings['transitions'][$data['transition']]['to']))
            {
                $transition_to = $this->settings['transitions'][$data['transition']]['to'];

                if ($this->canTransist($data['state'], $data['transition']))
                {
                    $data['state'] = $transition_to;
                    $data['transition'] = null;
                    $event->setData($data);
                }
            }

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
        $transition_to = $value;

        if (!$this->canTransist($current_state, $transition_to))
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

        $view->vars['current_state'] = "";
        $view->vars['current_state_label'] = "";

        if ($view->vars['value']['state']) {
            $view->vars['current_state_label'] = $this->settings['places'][$view->vars['value']['state']]['label'];
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
        return ['state' => $value];
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($value)
    {
        return $value['state'];
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
        return 'unite_cms_core_state';
    }
}
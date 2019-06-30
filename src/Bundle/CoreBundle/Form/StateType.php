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
use Symfony\Contracts\Translation\TranslatorInterface;

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
        $this->settings = $options['settings'];

        $builder->add('state', HiddenType::class,
            [
                'empty_data' => $this->settings['initial_place'],
                'constraints' => [
                    new Callback(['callback' => [$this, 'validPlaces']]),
                ],
            ]
        );

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($options) {

            // Set default value to initial_place
            if(empty($event->getData())) {
                $event->setData($this->settings['initial_place']);
            }

            $state = $event->getData();
            $form = $event->getForm();

            // add transition form type with reachable states (based on current state).
            $form->add('transition', ChoiceType::class, [
                'choices' => $this->getChoices($this->settings['transitions']),
                'placeholder' => $options['label_prefix'].'.field.placeholder',
                'constraints' => [
                    new Callback(
                        ['callback' => [$this, 'validTransitions']]
                    ),
                ],
                'choice_attr' => function($key, $val, $index) use ($state) {
                    return array_merge(
                        [
                            'data-category' => $this->getPlaceCategory($key),
                            'data-state-label' => $this->getPlaceLabel($key),
                        ],
                    $this->canTransist($state, $key) ? [] : ['disabled' => 'disabled']
                    );
                }
            ]);
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
        $view->vars['current_state_label'] = $this->settings['places'][$view->vars['value']['state']]['label'];
        $view->vars['current_state_category'] = $this->settings['places'][$view->vars['value']['state']]['category'] ?? '';
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
            'compound' => true,
            'error_bubbling' => true
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
            return false;
        }

        $transition = $this->settings['transitions'][$transition_to];

        if (in_array($current_state, $transition['from']))
        {
            return true;
        }

        return false;
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
     * @param string $transition_key
     *
     * @return string
     */
    private function getPlaceLabel(string $transition_key) : string
    {
        $place = $this->settings['transitions'][$transition_key]['to'];

        if (isset($this->settings['places'][$place]['label'])) {
            return $this->settings['places'][$place]['label'];
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
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'unite_cms_core_state';
    }
}
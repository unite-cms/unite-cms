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
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Form\AbstractType;
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

        $builder->add('last_transition', ChoiceType::class,
            [
                'label' => $options['label_prefix'].'.field.label',
                'choices' => $this->getChoices($this->settings['transitions']),
                'placeholder' => $options['label_prefix'].'.field.placeholder'
            ]
        );

        $builder->add('state', HiddenType::class, [] );

        #$builder->addEventListener(
        #    FormEvents::PRE_SET_DATA,
        #    [$this, 'onPreSetData']
        #);

        $builder->addModelTransformer($this);

    }

    public function onPreSetData(FormEvent $event)
    {
        $data = $event->getData();
        $form = $event->getForm();
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
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
        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($value)
    {
        // if a transition is given, pass the destination to state field
        if ($value['last_transition'])
        {
            $value['state'] = $this->settings['transitions'][$value['last_transition']]['to'];
        }

        // if saved for the first time without a transition, set intial place
        if (!$value['state'] && !$value['last_transition'])
        {
            $value['state'] = $this->settings['initial_place'];
        }

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'state';
    }
}
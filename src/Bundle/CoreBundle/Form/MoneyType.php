<?php
/**
 * Created by PhpStorm.
 * User: stefankamsker
 * Date: 17.09.18
 * Time: 14:19
 */

namespace UniteCMS\CoreBundle\Form;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Extension\Core\Type\CurrencyType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType as SymfonyMoneyType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Intl\Currencies;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MoneyType extends AbstractType implements DataTransformerInterface
{
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'currencies' => [],
            'compound' => true,
            'error_bubbling' => false,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $currencyOptions = [ 'label' => false ];

        if(!empty($options['currencies']) && is_array($options['currencies'])) {
            $currencyOptions['choices'] = [];
            $currencyOptions['choice_loader'] = null;
            foreach($options['currencies'] as $currency) {
                $currencyOptions['choices'][Currencies::getName($currency)] = $currency;
            }
        }

        $builder
            ->add('value', SymfonyMoneyType::class, [
                'label' => false,
                'currency' => false,
                'not_empty' => $options['not_empty'],
                'error_bubbling' => true,
            ])
            ->add('currency', CurrencyType::class, $currencyOptions);

        $builder->addModelTransformer($this);
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
        if (empty($value) || $value['value'] === null) {
            return null;
        }

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'unite_cms_core_money';
    }

}
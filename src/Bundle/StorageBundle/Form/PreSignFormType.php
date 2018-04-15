<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 09.02.18
 * Time: 08:42
 */

namespace UniteCMS\StorageBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

class PreSignFormType extends AbstractType implements DataTransformerInterface
{
    /**
     * @var TokenStorage $tokenStorage
     */
    private $tokenStorage;

    public function __construct(TokenStorage $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        if ($this->tokenStorage->getToken() && $this->tokenStorage->getToken()->getProviderKey() == "api") {
            $resolver->setDefault('csrf_protection', false);
        }
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addModelTransformer($this);
        $builder
            ->add('field', TextType::class, [
                'required' => true,
                'constraints' => [new NotBlank()]
            ])
            ->add('filename', TextType::class, [
                'required' => true,
                'constraints' => [new NotBlank()]
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public function transform($data)
    {
        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($data)
    {
        if (!empty($data['filename'])) {

            // Make filename lowercase.
            $data['filename'] = strtolower($data['filename']);
            // Replace spaces, underscores, and dashes with underscores.
            $data['filename'] = preg_replace('/(\s|_+|-+)+/', '_', $data['filename']);
            // Trim underscores from the ends.
            $data['filename'] = trim($data['filename'], '_');
            // Remove all except alpha-numeric and underscore characters.
            $data['filename'] = preg_replace('/[^a-z0-9_\.]+/', '', $data['filename']);
        }
        return $data;
    }
}

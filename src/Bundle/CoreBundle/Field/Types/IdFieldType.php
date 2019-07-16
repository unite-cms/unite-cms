<?php

namespace UniteCMS\CoreBundle\Field\Types;

use Doctrine\ORM\EntityRepository;
use GraphQL\Type\Definition\Type;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use UniteCMS\CoreBundle\Entity\FieldableContent;
use UniteCMS\CoreBundle\Entity\FieldableField;
use UniteCMS\CoreBundle\Field\FieldType;
use UniteCMS\CoreBundle\SchemaType\SchemaTypeManager;

class IdFieldType extends FieldType
{
    const TYPE = "id";
    const FORM_TYPE = TextType::class;
    const SETTINGS = ['description', 'form_group'];

    /**
     * {@inheritdoc}
     */
    function getFormOptions(FieldableField $field): array
    {
        return array_merge(parent::getFormOptions($field), [
            'attr' => [
                'readonly' => true,
                'placeholder' => 'Will be generated on create.',
            ],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function onCreate(FieldableField $field, FieldableContent $content, EntityRepository $repository, &$data) {
        $data[$field->getIdentifier()] = Uuid::uuid4()->toString();
    }

    /**
     * {@inheritdoc}
     */
    public function onUpdate(FieldableField $field, FieldableContent $content, EntityRepository $repository, $old_data, &$data) {
        $data[$field->getIdentifier()] = $old_data[$field->getIdentifier()] ?? Uuid::uuid4()->toString();
    }

    /**
     * {@inheritdoc}
     */
    function getGraphQLType(FieldableField $field, SchemaTypeManager $schemaTypeManager)
    {
        return Type::ID();
    }

    /**
     * {@inheritdoc}
     */
    function getGraphQLInputType(FieldableField $field, SchemaTypeManager $schemaTypeManager)
    {
        return null;
    }
}

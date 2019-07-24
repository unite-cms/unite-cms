<?php

namespace UniteCMS\CoreBundle\Field\Types;

use Doctrine\ORM\EntityRepository;
use GraphQL\Type\Definition\Type;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use UniteCMS\CoreBundle\Entity\FieldableContent;
use UniteCMS\CoreBundle\Entity\FieldableField;
use UniteCMS\CoreBundle\Field\FieldType;
use UniteCMS\CoreBundle\SchemaType\SchemaTypeManager;

class TokenFieldType extends FieldType
{
    const TYPE = "token";
    const FORM_TYPE = TextType::class;
    const SETTINGS = ['description', 'form_group'];

    /**
     * Generates a random url-ready token.
     * This line was taken from https://github.com/FriendsOfSymfony/FOSUserBundle/blob/master/Util/TokenGenerator.php
     *
     * @return string
     * @throws \Exception
     */
    static function generateToken() : string {
        return rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
    }

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
        $data[$field->getIdentifier()] = static::generateToken();
    }

    /**
     * {@inheritdoc}
     */
    public function onUpdate(FieldableField $field, FieldableContent $content, EntityRepository $repository, $old_data, &$data) {
        $data[$field->getIdentifier()] = $old_data[$field->getIdentifier()] ?? static::generateToken();
    }

    /**
     * {@inheritdoc}
     */
    function getGraphQLType(FieldableField $field, SchemaTypeManager $schemaTypeManager)
    {
        return Type::STRING();
    }

    /**
     * {@inheritdoc}
     */
    function getGraphQLInputType(FieldableField $field, SchemaTypeManager $schemaTypeManager)
    {
        return null;
    }
}

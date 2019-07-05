<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 06.08.18
 * Time: 09:22
 */

namespace UniteCMS\VariantsFieldBundle\SchemaType\Factories;

use UniteCMS\CoreBundle\Entity\Domain;
use UniteCMS\CoreBundle\Entity\DomainMemberType;
use UniteCMS\CoreBundle\Entity\SettingType;
use UniteCMS\CoreBundle\Field\NestableFieldTypeInterface;
use UniteCMS\CoreBundle\Model\FieldableFieldContent;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use UniteCMS\CoreBundle\Entity\ContentType;
use UniteCMS\CoreBundle\Entity\Fieldable;
use UniteCMS\CoreBundle\Entity\FieldableField;
use UniteCMS\CoreBundle\Field\FieldTypeInterface;
use UniteCMS\CoreBundle\Field\FieldTypeManager;
use UniteCMS\CoreBundle\SchemaType\Factories\SchemaTypeFactoryInterface;
use UniteCMS\CoreBundle\SchemaType\IdentifierNormalizer;
use UniteCMS\CoreBundle\SchemaType\SchemaTypeManager;
use UniteCMS\CoreBundle\Security\Voter\FieldableFieldVoter;
use UniteCMS\CoreBundle\Service\FieldableContentManager;
use UniteCMS\VariantsFieldBundle\Field\Types\VariantsFieldType;
use UniteCMS\VariantsFieldBundle\Model\Variant;
use UniteCMS\VariantsFieldBundle\Model\VariantContent;
use UniteCMS\VariantsFieldBundle\Model\Variants;

class VariantFactory implements SchemaTypeFactoryInterface
{
    private $fieldTypeManager;
    protected $authorizationChecker;
    protected $contentManager;

    public function __construct(FieldTypeManager $fieldTypeManager, AuthorizationCheckerInterface $authorizationChecker, FieldableContentManager $contentManager)
    {
        $this->fieldTypeManager = $fieldTypeManager;
        $this->authorizationChecker = $authorizationChecker;
        $this->contentManager = $contentManager;
    }

    /**
     * Returns the full object name of a variant that is resolvable via schema type manager.
     *
     * @param Fieldable $fieldable
     * @param bool $input
     * @return string
     */
    static function schemaTypeNameForVariant(Fieldable $fieldable, $input = false) {
        $identifierParts = explode('/', $fieldable->getIdentifierPath('/'));
        $identifierName = ucfirst(array_shift($identifierParts));
        $identifierName .= ($fieldable->getRootEntity() instanceof ContentType ? 'Content' : 'Setting');

        foreach($identifierParts as $part) {
            $identifierName .= ucfirst($part);
        }

        $identifierName .= 'Variant';

        if($input) {
            $identifierName .= 'Input';
        }

        return $identifierName;
    }

    public function createVariantType(SchemaTypeManager $schemaTypeManager, Variant $variant) : Type {

        $schemaTypeName = self::schemaTypeNameForVariant($variant);

        if(!$schemaTypeManager->hasSchemaType($schemaTypeName)) {

            /**
             * @var FieldableField[] $fields
             */
            $fields = [];

            /**
             * @var Type[] $fieldsSchemaTypes
             */
            $fieldsSchemaTypes = [];

            /**
             * @var FieldTypeInterface[] $fieldTypes
             */
            $fieldTypes = [];
            foreach($variant->getFields() as $field) {

                if(!$this->authorizationChecker->isGranted(FieldableFieldVoter::LIST, $field)) {
                    continue;
                }

                $fieldIdentifier = IdentifierNormalizer::graphQLIdentifier($field);
                $fields[$fieldIdentifier] = $field;
                $fieldTypes[$fieldIdentifier] = $this->fieldTypeManager->getFieldType($field->getType());
                $fieldsSchemaTypes[$fieldIdentifier] = $fieldTypes[$fieldIdentifier]->getGraphQLType($field, $schemaTypeManager);
            }

            $schemaTypeManager->registerSchemaType(new ObjectType([
                'name' => $schemaTypeName,
                'fields' => array_merge(
                    [
                        'type' => Type::string(),
                    ],
                    $fieldsSchemaTypes
                ),
                'interfaces' =>[ $schemaTypeManager->getSchemaType('VariantsFieldInterface') ],
                'resolveField' => function($value, array $args, $context, ResolveInfo $info) use ($fields, $fieldTypes) {

                    if(!$value instanceof Variant) {
                        throw new \InvalidArgumentException(
                            'Value must be instance of '.Variant::class.'.'
                        );
                    }

                    if($info->fieldName === 'type') {
                        return $value->getIdentifier();
                    }

                    if(!isset($fieldTypes[$info->fieldName]) || !isset($fields[$info->fieldName]) || !isset($value->getData()[$info->fieldName])) {
                        return null;
                    }

                    $variantContent = new VariantContent($value, $value->getData());

                    if(!$this->authorizationChecker->isGranted(FieldableFieldVoter::VIEW, new FieldableFieldContent($fields[$info->fieldName], $variantContent))) {
                        return null;
                    }

                    $return_value = null;
                    $fieldType = $this->fieldTypeManager->getFieldType($fieldTypes[$info->fieldName]->getType());
                    $return_value = $fieldType->resolveGraphQLData(
                        $fields[$info->fieldName],
                        $value->getData()[$info->fieldName],
                        $variantContent,
                        $args,
                        $context,
                        $info
                    );
                    return $return_value;
                }
            ]), false);
        }
        return $schemaTypeManager->getSchemaType($schemaTypeName);
    }

    public function createVariantsInputType(SchemaTypeManager $schemaTypeManager, Variants $variants) {

        $schemaTypeName = self::schemaTypeNameForVariant($variants, true);

        if(!$schemaTypeManager->hasSchemaType($schemaTypeName)) {

            /**
             * @var Type[] $fieldsSchemaTypes
             */
            $fieldsSchemaTypes = [];

            foreach($variants->getVariantsMetadata() as $meta) {

                $variant = new Variant(
                    null,
                    $variants->getFieldsForVariant($meta['identifier']),
                    $meta['identifier'],
                    $meta['title'],
                    $variants
                );

                $variantFieldsSchemaTypes = [];

                foreach($variant->getFields() as $field) {
                    $variantFieldsSchemaTypes[IdentifierNormalizer::graphQLIdentifier($field)] = $this->fieldTypeManager->getFieldType($field->getType())->getGraphQLInputType($field, $schemaTypeManager);

                    // field type can also return null, if no input / output is defined for this field.
                    if(!$variantFieldsSchemaTypes[IdentifierNormalizer::graphQLIdentifier($field)]) {
                        unset($variantFieldsSchemaTypes[IdentifierNormalizer::graphQLIdentifier($field)]);
                    }
                }

                if($variantFieldsSchemaTypes) {
                    $fieldsSchemaTypes[$meta['identifier']] = new InputObjectType(
                        [
                            'name' => $schemaTypeName.ucfirst($meta['identifier']),
                            'fields' => $variantFieldsSchemaTypes,
                        ]
                    );
                }
            }

            $schemaTypeManager->registerSchemaType(new InputObjectType([
                'name' => $schemaTypeName,
                'fields' => array_merge(
                    [
                        'type' => Type::string(),
                    ],
                    $fieldsSchemaTypes
                ),
            ]));
        }
        return $schemaTypeManager->getSchemaType($schemaTypeName);
    }

    /**
     * {@inheritDoc}
     */
    public function supports(string $schemaTypeName): bool
    {
        $nameParts = IdentifierNormalizer::graphQLSchemaSplitter($schemaTypeName);
        if(count($nameParts) < 5) {
            return false;
        }
        $lastPart = array_pop($nameParts);
        return $lastPart === 'Variant';
    }

    protected function findVariantField(Fieldable $fieldable, array $typeNameParts = []) : FieldableField {
        $root = strtolower(array_shift($typeNameParts));

        if(!$fieldable->getFields()->containsKey($root)) {
            throw new \InvalidArgumentException(sprintf('Fieldable %s does to contain field %s.', $fieldable->getIdentifier(), $root));
        }

        $field = $fieldable->getFields()->get($root);

        if($field->getType() === VariantsFieldType::getType()) {
            return $field;
        }

        if(!empty($typeNameParts)) {
            $fieldType = $this->fieldTypeManager->getFieldType($field->getType());

            if ($fieldType instanceof NestableFieldTypeInterface) {
                return $this->findVariantField($fieldType::getNestableFieldable($field), $typeNameParts);
            }
        }

        throw new \InvalidArgumentException(sprintf('Fieldable %s does to contain field %s.', $fieldable->getIdentifier(), $root));
    }

    /**
     * {@inheritDoc}
     */
    public function createSchemaType(
        SchemaTypeManager $schemaTypeManager,
        Domain $domain = null,
        string $schemaTypeName
    ): Type {

        if(!$domain) {
            throw new \InvalidArgumentException(sprintf('%s needs an domain as second argument,', self::class));
        }

        $nameParts = IdentifierNormalizer::graphQLSchemaSplitter($schemaTypeName);

        array_pop($nameParts);
        $variantName = strtolower(array_pop($nameParts));
        $identifier = strtolower(array_shift($nameParts));
        $type = strtolower(array_shift($nameParts));

        if($type === 'setting') {
            $type = SettingType::class;
        }

        if($type === 'content') {
            $type = ContentType::class;
        }

        if($type === 'member') {
            $type = DomainMemberType::class;
        }

        $fieldable = $this->contentManager->findFieldable($domain, $identifier, $type);
        $field = $this->findVariantField($fieldable, $nameParts);
        $variants = VariantsFieldType::getNestableFieldable($field);
        return $this->createVariantType($schemaTypeManager, new Variant(null, $variants->getFieldsForVariant($variantName), $variantName, $variantName, $variants));
    }
}
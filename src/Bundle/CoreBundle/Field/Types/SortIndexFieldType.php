<?php

namespace UniteCMS\CoreBundle\Field\Types;

use Doctrine\ORM\EntityRepository;
use GraphQL\Type\Definition\Type;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use UniteCMS\CoreBundle\Entity\Content;
use UniteCMS\CoreBundle\Entity\FieldableContent;
use UniteCMS\CoreBundle\Entity\FieldableField;
use UniteCMS\CoreBundle\Field\FieldType;
use UniteCMS\CoreBundle\SchemaType\SchemaTypeManager;

class SortIndexFieldType extends FieldType
{
    const TYPE = "sortindex";
    const FORM_TYPE = IntegerType::class;
    const SETTINGS = ['description'];

    function getGraphQLType(FieldableField $field, SchemaTypeManager $schemaTypeManager, $nestingLevel = 0)
    {
        return Type::int();
    }

    function getGraphQLInputType(FieldableField $field, SchemaTypeManager $schemaTypeManager, $nestingLevel = 0)
    {
        return Type::int();
    }

    /**
     * {@inheritdoc}
     */
    function resolveGraphQLData(FieldableField $field, $value, FieldableContent $content)
    {
        return (int)$value;
    }

    /**
     * {@inheritdoc}
     */
    function getDefaultValue(FieldableField $field)
    {
        return 0;
    }

    public function onCreate(FieldableField $field, Content $content, EntityRepository $repository, &$data)
    {
        $data[$field->getIdentifier()] = $repository->count(['contentType' => $content->getContentType()]);
    }

    public function onUpdate(
        FieldableField $field,
        FieldableContent $content,
        EntityRepository $repository,
        $old_data,
        &$data
    ) {
        if ($content instanceof Content) {

            // If this field is used to sort a tree view, we need to:
            // 1. Get the view's children_field
            // 2. Get the children_field's reference_field (parent)
            // 3. Restrict the renumbering queries to only affect entities with the same value in reference_field
            // TODO: Handle multiple tree views.
            $parentJsonId = null;
            $parentValue = null;
            foreach ($content->getContentType()->getViews() as $view) {
                if ($view->getType() === 'tree') {
                    $settings = $view->getSettings();
                    if ($field->getIdentifier() === $settings->sort['field']) {
                        $childrenField = $content->getContentType()->getFields()[$settings->children_field];
                        $parentField = $content->getContentType()->getFields()[$childrenField->getSettings()->reference_field];

                        $parentValue = $content->getData()[$parentField->getIdentifier()];
                        if ($parentValue === null) {
                            $parentJsonId = $parentField->getJsonExtractIdentifier();
                        } else {
                            $parentJsonId = $parentField->getJsonExtractIdentifier() . '.content';
                            $parentValue = $parentValue['content'];
                        }
                        break;
                    }
                }
            }

            // if we recover a deleted content, it's like we are moving the item from the end of the list to its original position.
            $originalPosition = null;

            // Get the old position, if available.

            if (isset($old_data[$field->getIdentifier()])) {
                $originalPosition = $old_data[$field->getIdentifier()];
            }

            // Get new position.
            $updatedPosition = $data[$field->getIdentifier()];

            // If we shift left, all items in between must be shifted right.
            if ($originalPosition !== null && $originalPosition > $updatedPosition) {

                $queryBuilder = $repository->createQueryBuilder('c')
                    ->update('UniteCMSCoreBundle:Content', 'c')
                    ->set('c.data', "JSON_SET(c.data, :identifier, CAST(JSON_UNQUOTE(JSON_EXTRACT(c.data, :identifier)) +1 AS int))")
                    ->where('c.contentType = :contentType')
                    ->andWhere("JSON_UNQUOTE(JSON_EXTRACT(c.data, :identifier)) BETWEEN :first AND :last")
                    ->setParameters(
                        [
                            'identifier' => $field->getJsonExtractIdentifier(),
                            ':contentType' => $content->getContentType(),
                            ':first' => $updatedPosition,
                            ':last' => $originalPosition - 1,
                        ]
                    );

                if ($parentJsonId !== null) {
                    $queryBuilder->andWhere("JSON_UNQUOTE(JSON_EXTRACT(c.data, :parent_identifier)) = :parent_value");
                    $queryBuilder->setParameter(':parent_identifier', $parentJsonId);
                    $queryBuilder->setParameter(':parent_value', $parentValue ?? 'null');
                }

                $queryBuilder->getQuery()->execute();
            }

            // if we shift right, all items in between must be shifted left.
            if ($originalPosition !== null && $originalPosition < $updatedPosition) {

                $queryBuilder = $repository->createQueryBuilder('c')
                    ->update('UniteCMSCoreBundle:Content', 'c')
                    ->set('c.data', "JSON_SET(c.data, :identifier, CAST(JSON_UNQUOTE(JSON_EXTRACT(c.data, :identifier)) -1 AS int))")
                    ->where('c.contentType = :contentType')
                    ->andWhere("JSON_UNQUOTE(JSON_EXTRACT(c.data, :identifier)) BETWEEN :first AND :last")
                    ->setParameters(
                        [
                            'identifier' => $field->getJsonExtractIdentifier(),
                            ':contentType' => $content->getContentType(),
                            ':first' => $originalPosition + 1,
                            ':last' => $updatedPosition,
                        ]
                    );

                if ($parentJsonId !== null) {
                    $queryBuilder->andWhere("JSON_UNQUOTE(JSON_EXTRACT(c.data, :parent_identifier)) = :parent_value");
                    $queryBuilder->setParameter(':parent_identifier', $parentJsonId);
                    $queryBuilder->setParameter(':parent_value', $parentValue ?? 'null');
                }

                $queryBuilder->getQuery()->execute();
            }

            // If we have no originalPosition, for example if we recover a deleted content.
            if ($originalPosition === null) {

                $queryBuilder = $repository->createQueryBuilder('c')
                    ->update('UniteCMSCoreBundle:Content', 'c')
                    ->set('c.data', "JSON_SET(c.data, :identifier, CAST(JSON_UNQUOTE(JSON_EXTRACT(c.data, :identifier)) +1 AS int))")
                    ->where('c.contentType = :contentType')
                    ->andWhere("JSON_UNQUOTE(JSON_EXTRACT(c.data, :identifier)) >= :first")
                    ->setParameters(
                        [
                            'identifier' => $field->getJsonExtractIdentifier(),
                            ':contentType' => $content->getContentType(),
                            ':first' => $updatedPosition,
                        ]
                    );

                if ($parentJsonId !== null) {
                    $queryBuilder->andWhere("JSON_UNQUOTE(JSON_EXTRACT(c.data, :parent_identifier)) = :parent_value");
                    $queryBuilder->setParameter(':parent_identifier', $parentJsonId);
                    $queryBuilder->setParameter(':parent_value', $parentValue ?? 'null');
                }

                $queryBuilder->getQuery()->execute();
            }

        }
    }

    public function onSoftDelete(FieldableField $field, Content $content, EntityRepository $repository, $data)
    {

        // all content after the deleted one should get --.
        $repository->createQueryBuilder('c')
            ->update('UniteCMSCoreBundle:Content', 'c')
            ->set('c.data', "JSON_SET(c.data, :identifier, CAST(JSON_UNQUOTE(JSON_EXTRACT(c.data, :identifier)) -1 AS int))")
            ->where('c.contentType = :contentType')
            ->andWhere("JSON_UNQUOTE(JSON_EXTRACT(c.data, :identifier)) > :last")
            ->setParameters(
                [
                    'identifier' => $field->getJsonExtractIdentifier(),
                    ':contentType' => $content->getContentType(),
                    ':last' => $data[$field->getIdentifier()],
                ]
            )
            ->getQuery()->execute();
    }
}

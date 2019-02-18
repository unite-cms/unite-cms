<?php

namespace UniteCMS\CoreBundle\Field\Types;

use Doctrine\ORM\EntityRepository;
use GraphQL\Type\Definition\Type;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Doctrine\ORM\NonUniqueResultException;
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
        // If this field is used to sort a tree view, then we must only count
        // items with the same parent value.
        list($parentJsonId, $parentValue) = $this->findParentFieldValue($field, $content, $data);
        if ($parentJsonId !== null) {
            try {
                $count = $repository->createQueryBuilder('c')
                    ->select('COUNT(c)')
                    ->andWhere('c.contentType = :contentType')
                    ->andWhere("JSON_UNQUOTE(JSON_EXTRACT(c.data, :parent_identifier)) = :parent_value")
                    ->setParameter(':parent_identifier', $parentJsonId)
                    // If this is a top-level item, JSON-extracting its parent value field will
                    // return the string 'null'.
                    ->setParameter(':parent_value', $parentValue ?? 'null')
                    ->setParameter(':contentType', $content->getContentType())
                    ->getQuery()
                    ->getSingleScalarResult();
            } catch (NonUniqueResultException $e) {
                // This exception should never be thrown, COUNT() always returns a single value.
                $count = 0;
            }
        } else {
            $count = $repository->count(['contentType' => $content->getContentType()]);
        }

        $data[$field->getIdentifier()] = $count;
    }

    public function onUpdate(
        FieldableField $field,
        FieldableContent $content,
        EntityRepository $repository,
        $old_data,
        &$data
    ) {
        if ($content instanceof Content) {

            // If we recover a deleted content, it's like we are moving the item from the end of the list to its original position.
            $originalPosition = null;

            // Get the old position, if available.
            if (isset($old_data[$field->getIdentifier()])) {
                $originalPosition = $old_data[$field->getIdentifier()];
            }

            // Get new position.
            $updatedPosition = $data[$field->getIdentifier()];

            $queryBuilder = $repository->createQueryBuilder('c');


            list($parentJsonId, $parentValue) = $this->findParentFieldValue($field, $content, $data);

            // If this field is used to sort a tree view, make sure we only re-number tree sibling items.
            if ($parentJsonId !== null) {
                $queryBuilder->andWhere("JSON_UNQUOTE(JSON_EXTRACT(c.data, :parent_identifier)) = :parent_value");
                $queryBuilder->setParameter(':parent_identifier', $parentJsonId);
                // If this is a top-level item, JSON-extracting its parent value field will
                // return the string 'null'.
                $queryBuilder->setParameter(':parent_value', $parentValue ?? 'null');
            }

            // If we shift left, all items in between must be shifted right.
            if ($originalPosition !== null && $originalPosition > $updatedPosition) {

                $queryBuilder->update('UniteCMSCoreBundle:Content', 'c')
                    ->set('c.data', "JSON_SET(c.data, :identifier, CAST(JSON_UNQUOTE(JSON_EXTRACT(c.data, :identifier)) +1 AS int))")
                    ->andWhere('c.contentType = :contentType')
                    ->andWhere("JSON_UNQUOTE(JSON_EXTRACT(c.data, :identifier)) BETWEEN :first AND :last")
                    ->setParameter('identifier', $field->getJsonExtractIdentifier())
                    ->setParameter(':contentType', $content->getContentType())
                    ->setParameter(':first', $updatedPosition)
                    ->setParameter(':last', $originalPosition - 1)
                    ->getQuery()->execute();
            }

            // if we shift right, all items in between must be shifted left.
            if ($originalPosition !== null && $originalPosition < $updatedPosition) {

                $queryBuilder->update('UniteCMSCoreBundle:Content', 'c')
                    ->set('c.data', "JSON_SET(c.data, :identifier, CAST(JSON_UNQUOTE(JSON_EXTRACT(c.data, :identifier)) -1 AS int))")
                    ->andWhere('c.contentType = :contentType')
                    ->andWhere("JSON_UNQUOTE(JSON_EXTRACT(c.data, :identifier)) BETWEEN :first AND :last")
                    ->setParameter('identifier', $field->getJsonExtractIdentifier())
                    ->setParameter(':contentType', $content->getContentType())
                    ->setParameter(':first', $originalPosition + 1)
                    ->setParameter(':last', $updatedPosition)
                    ->getQuery()->execute();
            }

            // If we have no originalPosition, for example if we recover a deleted content.
            if ($originalPosition === null) {

                $queryBuilder->update('UniteCMSCoreBundle:Content', 'c')
                    ->set('c.data', "JSON_SET(c.data, :identifier, CAST(JSON_UNQUOTE(JSON_EXTRACT(c.data, :identifier)) +1 AS int))")
                    ->andWhere('c.contentType = :contentType')
                    ->andWhere("JSON_UNQUOTE(JSON_EXTRACT(c.data, :identifier)) >= :first")
                    ->setParameter('identifier', $field->getJsonExtractIdentifier())
                    ->setParameter(':contentType', $content->getContentType())
                    ->setParameter(':first', $updatedPosition)
                    ->getQuery()->execute();
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

    /**
     * If the given field is used to sort a tree view, returns data about the
     * tree view's reference field's parent field.
     *
     * Returns a two-element array consisting of:
     * 1. The parent field's JSON extract identifier, for use in a query.
     * 2. The parent field's value for the given content item.
     *
     * @param FieldableField $field
     * @param FieldableContent $content
     * @return array
     */
    private function findParentFieldValue(FieldableField $field, FieldableContent $content, $data)
    {
        $parentJsonId = null;
        $parentValue = null;
        if ($content instanceof Content) {
            // Check each view for this content type and see if there are any tree views.
            foreach ($content->getContentType()->getViews() as $view) {
                if ($view->getType() === 'tree') {
                    $settings = $view->getSettings();
                    // Check if this field is used to sort the tree view.
                    if ($field->getIdentifier() === $settings->sort['field']) {
                        // Get the children_field's reference_field (parent field)
                        $childrenField = $content->getContentType()->getFields()[$settings->children_field];
                        $parentField = $content->getContentType()->getFields()[$childrenField->getSettings()->reference_field];

                        // Get the parent field value for this content item. If null, this
                        // means the content item is top-level and there won't be any further
                        // data for the parent field.
                        $parentValue = $data[$parentField->getIdentifier()];
                        if ($parentValue === null) {
                            $parentJsonId = $parentField->getJsonExtractIdentifier();
                        } else {
                            // If it's not null, then we need to extract the 'content'
                            // sub-field's value.
                            $parentJsonId = $parentField->getJsonExtractIdentifier() . '.content';
                            $parentValue = $parentValue['content'];
                        }

                        // TODO: Handle multiple tree views.
                        break;
                    }
                }
            }
        }

        return [$parentJsonId, $parentValue];
    }
}

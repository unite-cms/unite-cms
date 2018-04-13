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

    function getGraphQLType(FieldableField $field, SchemaTypeManager $schemaTypeManager, $nestingLevel = 0)
    {
        return Type::int();
    }

    function getGraphQLInputType(FieldableField $field, SchemaTypeManager $schemaTypeManager, $nestingLevel = 0) {
        return Type::int();
    }

    public function onCreate(FieldableField $field, Content $content, EntityRepository $repository, &$data) {
        $data[$field->getIdentifier()] = $repository->count(['contentType' => $content->getContentType()]);
    }

    public function onUpdate(FieldableField $field, FieldableContent $content, EntityRepository $repository, $old_data, &$data) {
        if($content instanceof Content) {

            // if we recover a deleted content, it's like we are moving the item from the end of the list to its original position.
            $originalPosition = null;

            // Get the old position, if available.

            if(isset($old_data[$field->getIdentifier()])) {
                $originalPosition = $old_data[$field->getIdentifier()];
            }

            // Get new position.
            $updatedPosition = $data[$field->getIdentifier()];

            // If we shift left, all items in between must be shifted right.
            if($originalPosition !== null && $originalPosition > $updatedPosition) {

                $repository->createQueryBuilder('c')
                    ->update('UniteCMSCoreBundle:Content', 'c')
                    ->set('c.data', "JSON_SET(c.data, :identifier, CAST(JSON_EXTRACT(c.data, :identifier) +1 AS int))")
                    ->where('c.contentType = :contentType')
                    ->andWhere("JSON_EXTRACT(c.data, :identifier) BETWEEN :first AND :last")
                    ->setParameters([
                        'identifier' => $field->getJsonExtractIdentifier(),
                        ':contentType' => $content->getContentType(),
                        ':first' => $updatedPosition,
                        ':last' => $originalPosition - 1,
                    ])
                    ->getQuery()->execute();

            }

            // if we shift right, all items in between must be shifted left.
            if($originalPosition !== null && $originalPosition < $updatedPosition) {

                $repository->createQueryBuilder('c')
                    ->update('UniteCMSCoreBundle:Content', 'c')
                    ->set('c.data', "JSON_SET(c.data, :identifier, CAST(JSON_EXTRACT(c.data, :identifier) -1 AS int))")
                    ->where('c.contentType = :contentType')
                    ->andWhere("JSON_EXTRACT(c.data, :identifier) BETWEEN :first AND :last")
                    ->setParameters([
                        'identifier' => $field->getJsonExtractIdentifier(),
                        ':contentType' => $content->getContentType(),
                        ':first' => $originalPosition + 1,
                        ':last' => $updatedPosition,
                    ])
                    ->getQuery()->execute();
            }

            // If we have no originalPosition, for example if we recover a deleted content.
            if($originalPosition === null) {

                $repository->createQueryBuilder('c')
                    ->update('UniteCMSCoreBundle:Content', 'c')
                    ->set('c.data', "JSON_SET(c.data, :identifier, CAST(JSON_EXTRACT(c.data, :identifier) +1 AS int))")
                    ->where('c.contentType = :contentType')
                    ->andWhere("JSON_EXTRACT(c.data, :identifier) >= :first")
                    ->setParameters([
                        'identifier' => $field->getJsonExtractIdentifier(),
                        ':contentType' => $content->getContentType(),
                        ':first' => $updatedPosition,
                    ])
                    ->getQuery()->execute();
            }

        }
    }

    public function onSoftDelete(FieldableField $field, Content $content, EntityRepository $repository, $data) {

        // all content after the deleted one should get --.
        $repository->createQueryBuilder('c')
            ->update('UniteCMSCoreBundle:Content', 'c')
            ->set('c.data', "JSON_SET(c.data, :identifier, CAST(JSON_EXTRACT(c.data, :identifier) -1 AS int))")
            ->where('c.contentType = :contentType')
            ->andWhere("JSON_EXTRACT(c.data, :identifier) > :last")
            ->setParameters([
                'identifier' => $field->getJsonExtractIdentifier(),
                ':contentType' => $content->getContentType(),
                ':last' => $data[$field->getIdentifier()],
            ])
            ->getQuery()->execute();
    }
}

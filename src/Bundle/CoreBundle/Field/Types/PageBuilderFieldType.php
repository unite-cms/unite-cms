<?php

namespace UniteCMS\CoreBundle\Field\Types;

use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use UniteCMS\CoreBundle\Content\ContentInterface;
use UniteCMS\CoreBundle\Content\FieldData;
use UniteCMS\CoreBundle\ContentType\ContentType;
use UniteCMS\CoreBundle\ContentType\ContentTypeField;
use UniteCMS\CoreBundle\Domain\DomainManager;
use UniteCMS\CoreBundle\GraphQL\SchemaManager;

class PageBuilderFieldType extends AbstractFieldType
{
    const TYPE = 'pageBuilder';
    const GRAPHQL_INPUT_TYPE = 'UnitePageBuilderBlockInput';

    /**
     * @var DomainManager $domainManager
     */
    protected $domainManager;

    /**
     * @var SchemaManager $schemaManager
     */
    protected $schemaManager;

    /**
     * {@inheritDoc}
     */
    protected function allowedReturnTypes(ContentTypeField $field) {
        return ['UnitePageBuilderBlock'];
    }

    /**
     * {@inheritDoc}
     */
    public function validateFieldDefinition(ContentType $contentType, ContentTypeField $field, ExecutionContextInterface $context) : void {
        parent::validateFieldDefinition($contentType, $field, $context);

        if(!$field->isNonNull() || $field->isListOf()) {
            $context
                ->buildViolation('Invalid GraphQL return type "{{ return_type }}" for field of type "{{ type }}". Please use a nonNull single field ("PageBuilderBlock!").')
                ->setParameter('{{ type }}', static::getType())
                ->setParameter('{{ return_type }}', $field->getReturnType())
                ->addViolation();
            return;
        }
    }

    /**
     * {@inheritDoc}
     */
    protected function resolveRowData(ContentInterface $content, ContentTypeField $field, FieldData $fieldData, array $args = []) {

        $data = [
            'JSON' => $fieldData->resolveData('JSON', ''),
            'HTML' => $fieldData->resolveData('HTML', ''),
            'type' => 'root',
            'attrs' => [],
            'content' => [],
        ];

        if(!empty($json)) {
            $blockDoc = json_decode($json);
            $data['type'] = $blockDoc->type;
            $data['attrs'] = $blockDoc->attrs;
            $data['content'] = $blockDoc->content;
        }

        return $data;
    }

    /**
     * {@inheritDoc}
     */
    public function getPublicSettings(ContentTypeField $field) : ?ArrayCollection {
        return new ArrayCollection([
            'customBlocks' => $field->getSettings()->get('customBlocks'),
            //'mediaType' => null,
            //'templateType' => null,
        ]);
    }
}

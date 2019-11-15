<?php

namespace UniteCMS\CoreBundle\Field\Types;

use GraphQL\Type\Definition\Type;
use UniteCMS\CoreBundle\Content\ContentInterface;
use UniteCMS\CoreBundle\Content\FieldData;
use UniteCMS\CoreBundle\ContentType\ContentTypeField;
use UniteCMS\CoreBundle\Domain\DomainManager;
use UniteCMS\CoreBundle\Expression\SaveExpressionLanguage;
use UniteCMS\CoreBundle\Query\ContentCriteria;
use UniteCMS\CoreBundle\Query\DataFieldOrderBy;

class SequenceType extends AbstractFieldType
{
    const TYPE = 'sequence';
    const GRAPHQL_INPUT_TYPE = null;

    /**
     * @var DomainManager $domainManager;
     */
    protected $domainManager;

    public function __construct(SaveExpressionLanguage $expressionLanguage, DomainManager $domainManager)
    {
        $this->domainManager = $domainManager;
        parent::__construct($expressionLanguage);
    }

    /**
     * {@inheritDoc}
     */
    protected function allowedReturnTypes(ContentTypeField $field) {
        return [Type::INT];
    }

    /**
     * {@inheritDoc}
     */
    public function normalizeInputData(ContentInterface $content, ContentTypeField $field, $inputData = null) : FieldData {

        $fieldData = $content->getFieldData($field->getId());

        // If data is present, just return it.
        if($fieldData && !$fieldData->empty()) {
            return $fieldData;
        }

        // If data is not present, create a new sequence.
        return new FieldData($this->generateNextSequence($content, $field));
    }

    /**
     * Generate a next sequence number for this content.
     *
     * @param ContentInterface $content
     * @param ContentTypeField $field
     *
     * @return int
     */
    protected function generateNextSequence(ContentInterface $content, ContentTypeField $field) : int {
        $domain = $this->domainManager->current();
        $criteria = new ContentCriteria();
        $criteria
            ->orderBy(new DataFieldOrderBy($field->getId(), ContentCriteria::DESC))
            ->setMaxResults(1);

        $maxContent = $domain->getContentManager()->find($domain, $content->getType(), $criteria);
        $result = $maxContent->getResult();

        if(empty($result)) {
            return 1;
        }

        $fieldData = $result[0]->getFieldData($field->getId());

        if(!$fieldData || $fieldData->empty()) {
            return 1;
        }

        return $fieldData->getData() + 1;
    }
}

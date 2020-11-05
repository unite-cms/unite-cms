<?php

namespace UniteCMS\CoreBundle\Field\Types;

use GraphQL\Type\Definition\Type;
use UniteCMS\CoreBundle\Content\ContentInterface;
use UniteCMS\CoreBundle\Content\FieldData;
use UniteCMS\CoreBundle\ContentType\ContentTypeField;
use UniteCMS\CoreBundle\Domain\DomainManager;
use UniteCMS\CoreBundle\Exception\UnknownFieldException;
use UniteCMS\CoreBundle\Expression\SaveExpressionLanguage;
use UniteCMS\CoreBundle\Query\ContentCriteria;
use UniteCMS\CoreBundle\Query\ContentCriteriaBuilder;
use UniteCMS\CoreBundle\Query\DataFieldComparison;
use UniteCMS\CoreBundle\Query\DataFieldOrderBy;
use UniteCMS\CoreBundle\Security\User\UserInterface;

class SequenceType extends AbstractFieldType
{
    const TYPE = 'sequence';
    const GRAPHQL_INPUT_TYPE = null;

    /**
     * @var DomainManager $domainManager;
     */
    protected $domainManager;

    /**
     * @var ContentCriteriaBuilder $contentCriteriaBuilder
     */
    protected $contentCriteriaBuilder;

    public function __construct(SaveExpressionLanguage $expressionLanguage, DomainManager $domainManager, ContentCriteriaBuilder $contentCriteriaBuilder)
    {
        $this->domainManager = $domainManager;
        $this->contentCriteriaBuilder = $contentCriteriaBuilder;
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
    public function normalizeInputData(ContentInterface $content, ContentTypeField $field, $inputData = null, int $rowDelta = null, array $rawInputData = []) : FieldData {

        $fieldData = $content->getFieldData($field->getId());

        // If data is present, just return it.
        if($fieldData && !$fieldData->empty()) {
            return $fieldData;
        }

        // If data is not present, create a new sequence.
        return new FieldData($this->generateNextSequence($content, $field, $rawInputData));
    }

    /**
     * @param ContentInterface $content
     * @param ContentTypeField $field
     * @param FieldData $fieldData
     * @param array $args
     *
     * @return mixed
     */
    protected function resolveRowData(ContentInterface $content, ContentTypeField $field, FieldData $fieldData, array $args = []) {
        return $fieldData->resolveData('', ($field->isNonNull() || $field->isListOf()) ? 0 : null);
    }

    /**
     * Generate a next sequence number for this content.
     *
     * @param ContentInterface $content
     * @param ContentTypeField $field
     *
     * @param array $rawInputData
     * @return int
     * @throws UnknownFieldException
     */
    protected function generateNextSequence(ContentInterface $content, ContentTypeField $field, array $rawInputData = []) : int {
        $domain = $this->domainManager->current();
        $contentType = $domain->getContentTypeManager()->getAnyType($content->getType());
        $criteria = $this->contentCriteriaBuilder->build([
            'limit' => 1,
            'orderBy' => [[
                'field' => $field->getId(),
                'order' => ContentCriteria::DESC,
            ]],
            'filter' => $this->applyInputValues($field->getSettings()->get('criteria', []), $rawInputData),
        ], $contentType);

        $manager = $content instanceof UserInterface ? $domain->getUserManager() : $domain->getContentManager();
        $maxContent = $manager->find($domain, $content->getType(), $criteria, true);
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

    protected function applyInputValues(array $criteria = [], array $values = []) : array {

        if(!empty($criteria['AND'])) {
            $criteria['AND'] = $this->applyInputValues($criteria['AND'], $values);
        }
        if(!empty($criteria['OR'])) {
            $criteria['OR'] = $this->applyInputValues($criteria['OR'], $values);
        }
        if(!empty($criteria['field']) && !empty($criteria['value'])) {
            if(!is_array($criteria['value'])) {
                $criteria['value'] = [$criteria['value']];
            }
            foreach($criteria['value'] as $key => $value) {
                $criteria['value'][$key] = $this->expressionLanguage->evaluate($value, $values);
            }
        }

        return $criteria;
    }
}

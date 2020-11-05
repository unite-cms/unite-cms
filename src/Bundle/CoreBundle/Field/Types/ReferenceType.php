<?php


namespace UniteCMS\CoreBundle\Field\Types;

use Doctrine\Common\Collections\Expr\Comparison;
use GraphQL\Type\Definition\Type;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Validator\ContextualValidatorInterface;
use UniteCMS\CoreBundle\Content\BaseContent;
use UniteCMS\CoreBundle\Content\ContentInterface;
use UniteCMS\CoreBundle\Content\FieldData;
use UniteCMS\CoreBundle\Content\FieldDataList;
use UniteCMS\CoreBundle\Content\ReferenceFieldData;
use UniteCMS\CoreBundle\ContentType\ContentType;
use UniteCMS\CoreBundle\ContentType\ContentTypeField;
use UniteCMS\CoreBundle\Domain\DomainManager;
use UniteCMS\CoreBundle\Expression\SaveExpressionLanguage;
use UniteCMS\CoreBundle\Query\BaseFieldComparison;
use UniteCMS\CoreBundle\Query\BaseFieldOrderBy;
use UniteCMS\CoreBundle\Query\ContentCriteria;
use UniteCMS\CoreBundle\Query\ReferenceDataFieldComparison;
use UniteCMS\CoreBundle\Query\ReferenceDataFieldOrderBy;
use UniteCMS\CoreBundle\Security\User\UserInterface;
use UniteCMS\CoreBundle\Security\Voter\ContentVoter;

class ReferenceType extends AbstractFieldType
{
    const TYPE = 'reference';
    const GRAPHQL_INPUT_TYPE = Type::ID;

    /**
     * @var DomainManager $domainManager
     */
    protected $domainManager;

    /**
     * @var AuthorizationCheckerInterface $authorizationChecker
     */
    protected $authorizationChecker;

    public function __construct(DomainManager $domainManager, SaveExpressionLanguage $saveExpressionLanguage, AuthorizationCheckerInterface $authorizationChecker)
    {
        $this->domainManager = $domainManager;
        $this->authorizationChecker = $authorizationChecker;
        parent::__construct($saveExpressionLanguage);
    }

    /**
     * {@inheritDoc}
     */
    public function validateFieldDefinition(ContentType $contentType, ContentTypeField $field, ExecutionContextInterface $context) : void {

        // Validate return type.
        $returnTypes = empty($field->getUnionTypes()) ? [$field->getReturnType()] : array_keys($field->getUnionTypes());
        $contentTypeManager = $this->domainManager->current()->getContentTypeManager();
        foreach($returnTypes as $returnType) {
            if(!$contentTypeManager->getContentType($returnType) && !$contentTypeManager->getUserType($returnType)) {
                $context
                    ->buildViolation('Invalid GraphQL return type "{{ return_type }}" for field of type "{{ type }}". Please use a GraphQL type (or an union of types) that implements UniteContent.')
                    ->setParameter('{{ type }}', static::getType())
                    ->setParameter('{{ return_type }}', $field->getReturnType())
                    ->addViolation();
            }
        }
    }

    /**
     * @param ContentTypeField $field
     * @param FieldData $fieldData
     * @return ContentInterface[]
     */
    protected function getContentForField(ContentTypeField $field, FieldData $fieldData) : array {

        if(empty($fieldData->getData())) {
            return [];
        }

        $domain = $this->domainManager->current();
        $contentManager = $domain->getContentTypeManager()->getContentType($field->getReturnType()) ? $domain->getContentManager() : $domain->getUserManager();

        if($fieldData instanceof FieldDataList) {

            $rowIds = [];

            foreach($fieldData->rows() as $rowData) {
                $rowIds[] = $rowData->getData();
            }

            // Find all content objects by id.
            $criteria = new ContentCriteria();
            $criteria->andWhere(
                new Comparison('id', Comparison::IN, array_unique($rowIds))
            );

            $result = $contentManager->find($domain, $field->getReturnType(), $criteria);
            $resultById = [];

            // The result will not include duplicates. We need to transform it for an exact result.
            foreach($result->getResult() as $content) {
                $resultById[$content->getId()] = $content;
            }

            $resolvedContent = [];
            foreach($rowIds as $rowId) {
                if(array_key_exists($rowId, $resultById)) {
                    $resolvedContent[] = $resultById[$rowId];
                }
            }

            return $resolvedContent;
        }

        $referencedContent = $contentManager->get($domain, $field->getReturnType(), $fieldData->getData());

        // With this little trick we make sure, that we don't run into GraphQL
        // issues for deleted references. Note, that the problem still exists
        // on sub fields of this content, but at least we can query the id,
        // which will be an empty string.
        if(!$referencedContent && $field->isNonNull()) {
            return [new class($field->getReturnType()) extends BaseContent {}];
        }

        if(!$this->authorizationChecker->isGranted(ContentVoter::READ, $referencedContent)) {
            return [];
        }

        return [$referencedContent];
    }

    /**
     * {@inheritDoc}
     */
    public function resolveField(ContentInterface $content, ContentTypeField $field, FieldData $fieldData, array $args = []) {

        $content = array_filter($this->getContentForField($field, $fieldData), function(ContentInterface $content){
            return $this->authorizationChecker->isGranted(ContentVoter::READ, $content);
        });

        if($field->isListOf()) {
            return $content;
        }

        return count($content) > 0 ? $content[0] : null;
    }

    /**
     * {@inheritDoc}
     */
    public function normalizeInputData(ContentInterface $content, ContentTypeField $field, $inputData = null, int $rowDelta = null, array $rawInputData = []) : FieldData {
        $fieldData = parent::normalizeInputData($content, $field, $inputData, $rowDelta, $rawInputData);
        return new ReferenceFieldData($fieldData->getData());
    }

    /**
     * {@inheritDoc}
     */
    public function validateFieldData(ContentInterface $content, ContentTypeField $field, ContextualValidatorInterface $validator, ExecutionContextInterface $context, FieldData $fieldData = null) : void {

        parent::validateFieldData($content, $field, $validator, $context, $fieldData);

        if($validator->getViolations()->count() > 0) {
            return;
        }

        // If we have no value, stop here.
        if((empty($fieldData) || empty($fieldData->resolveData()))) {
            return;
        }

        // Check that referenced content can be resolved.
        $validator->validate(
            $this->getContentForField($field, $fieldData),
            new NotNull(),
            [$context->getGroup()]
        );
    }

    /**
     * {@inheritDoc}
     */
    public function queryOrderBy(ContentTypeField $field, array $whereInput) : ?BaseFieldOrderBy {

        $parts = explode($whereInput['field'], '.');
        if(count($parts) > 1) {
            $domain = $this->domainManager->current();
            $entityType = $domain->getContentTypeManager()->getUserType($field->getReturnType()) ? UserInterface::class : ContentInterface::class;
            return new ReferenceDataFieldOrderBy(array_shift($parts), join('.', $parts), $whereInput['order'], $entityType);
        }

        return parent::queryOrderBy($field, $whereInput);
    }

    /**
     * {@inheritDoc}
     */
    public function queryComparison(ContentTypeField $field, array $whereInput) : ?BaseFieldComparison {

        $comparison = parent::queryComparison($field, $whereInput);

        if(empty($whereInput['path'])) {
            return $comparison;
        }

        $domain = $this->domainManager->current();
        $entityType = $domain->getContentTypeManager()->getUserType($field->getReturnType()) ? UserInterface::class : ContentInterface::class;

        return new ReferenceDataFieldComparison(
            $field->getId(),
            $comparison->getOperator(),
            $comparison->getValue(),
            ['data'],
            $field->getReturnType(),
            $whereInput['path'],
            $entityType
        );
    }
}

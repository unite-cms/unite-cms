<?php


namespace UniteCMS\CoreBundle\Field\Types;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Expr\Comparison;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use UniteCMS\CoreBundle\Content\ContentInterface;
use UniteCMS\CoreBundle\Content\FieldData;
use UniteCMS\CoreBundle\ContentType\ContentType;
use UniteCMS\CoreBundle\ContentType\ContentTypeField;
use UniteCMS\CoreBundle\Domain\DomainManager;
use UniteCMS\CoreBundle\Expression\SaveExpressionLanguage;
use UniteCMS\CoreBundle\Query\ContentCriteriaBuilder;
use UniteCMS\CoreBundle\Query\DataFieldComparison;

class ReferenceOfType extends AbstractFieldType
{
    const TYPE = 'referenceOf';
    const GRAPHQL_INPUT_TYPE = null;

    /**
     * @var ContentCriteriaBuilder $criteriaBuilder
     */
    protected $criteriaBuilder;

    /**
     * @var DomainManager $domainManager
     */
    protected $domainManager;

    public function __construct(ContentCriteriaBuilder $criteriaBuilder, DomainManager $domainManager, SaveExpressionLanguage $saveExpressionLanguage)
    {
        $this->criteriaBuilder = $criteriaBuilder;
        $this->domainManager = $domainManager;
        parent::__construct($saveExpressionLanguage);
    }

    /**
     * {@inheritDoc}
     */
    protected function allowedReturnTypes(ContentTypeField $field) {
        return ['UniteContentResult'];
    }

    /**
     * @param ContentTypeField $field
     *
     * @return ContentType|null
     */
    protected function getReferenceContentType(ContentTypeField $field) : ?ContentType {
        $referencedContentType = $this->domainManager->current()->getContentTypeManager()->getContentType($field->getSettings()->get('content_type'));
        return $referencedContentType ?? $this->domainManager->current()->getContentTypeManager()->getUserType($field->getSettings()->get('content_type'));
    }

    /**
     * @param string $contentType
     * @param ContentTypeField $field
     *
     * @return ContentTypeField|null
     */
    protected function getReferenceField(string $contentType, ContentTypeField $field) : ?ContentTypeField {
        if(!$referencedContentType = $this->getReferenceContentType($field)) {
            return null;
        }

        $referenceField = $referencedContentType->getField($field->getSettings()->get('reference_field'));
        if(!$referenceField || $referenceField->getReturnType() !== $contentType) {
            return null;
        }

        return $referenceField;
    }

    /**
     * {@inheritDoc}
     */
    public function validateFieldDefinition(ContentType $contentType, ContentTypeField $field, ExecutionContextInterface $context) : void {

        parent::validateFieldDefinition($contentType, $field, $context);

        if($context->getViolations()->count() > 0) {
            return;
        }

        if($field->isNonNull()) {
            $context
                ->buildViolation('The return type of a reference_of field cannot be nonNull.')
                ->addViolation();
            return;
        }

        if(!$field->getSettings()->has('content_type')) {
            $context
                ->buildViolation('Please set the "content_type" field of "{{ type }}".')
                ->setParameter('{{ type }}', static::getType())
                ->addViolation();
            return;
        }

        if(!$field->getSettings()->has('reference_field')) {
            $context
                ->buildViolation('Please set the "reference_field" field of type "{{ type }}".')
                ->setParameter('{{ type }}', static::getType())
                ->addViolation();
            return;
        }

        // Validate content type
        if(!$this->getReferenceField($contentType->getId(), $field)) {
            $context
                ->buildViolation('Invalid content_type or reference_field setting for field of type "{{ type }}". Please use a GraphQL type implements UniteContent or UniteUser with a reference to this type.')
                ->setParameter('{{ type }}', static::getType())
                ->setParameter('{{ content_type }}', $field->getSettings()->get('content_type'))
                ->addViolation();
            return;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getPublicSettings(ContentTypeField $field) : ?ArrayCollection {
        $settings = parent::getPublicSettings($field) ?? new ArrayCollection();
        $settings->set('reference_field', $field->getSettings()->get('reference_field'));
        $settings->set('content_type', $field->getSettings()->get('content_type'));
        return $settings;
    }

    /**
     * {@inheritDoc}
     */
    public function resolveField(ContentInterface $content, ContentTypeField $field, FieldData $fieldData, array $args = []) {

        if(empty($content->getId())) {
            return null;
        }

        $domain = $this->domainManager->current();
        $contentManager = $domain->getContentManager();

        if(!$reference_field = $this->getReferenceField($content->getType(), $field)){
            return null;
        }
        $referencedContentType = $this->getReferenceContentType($field);
        $criteria = $this->criteriaBuilder->build($args, $referencedContentType);

        $criteria->andWhere(new DataFieldComparison(
            $reference_field->getId(),
            $reference_field->isListOf() ? Comparison::CONTAINS : Comparison::EQ,
            $reference_field->isListOf() ? sprintf('"%s"', $content->getId()) : $content->getId()
        ));

        return $contentManager->find($domain, $field->getSettings()->get('content_type'), $criteria);
    }
}

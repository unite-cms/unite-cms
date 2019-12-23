<?php


namespace UniteCMS\CoreBundle\Field\Types;

use Doctrine\Common\Collections\ArrayCollection;
use GraphQL\Type\Definition\Type;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Validator\ContextualValidatorInterface;
use UniteCMS\CoreBundle\Content\ContentInterface;
use UniteCMS\CoreBundle\Content\FieldData;
use UniteCMS\CoreBundle\Content\FieldDataList;
use UniteCMS\CoreBundle\ContentType\ContentType;
use UniteCMS\CoreBundle\ContentType\ContentTypeField;
use UniteCMS\CoreBundle\Expression\SaveExpressionLanguage;
use UniteCMS\CoreBundle\Field\FieldTypeInterface;
use UniteCMS\CoreBundle\GraphQL\Schema\Provider\SchemaProviderInterface;
use UniteCMS\CoreBundle\Query\BaseFieldComparison;
use UniteCMS\CoreBundle\Query\ContentCriteria;
use UniteCMS\CoreBundle\Query\BaseFieldOrderBy;
use UniteCMS\CoreBundle\Query\DataFieldComparison;
use UniteCMS\CoreBundle\Query\DataFieldOrderBy;

abstract class AbstractFieldType  implements FieldTypeInterface, SchemaProviderInterface
{
    const TYPE = null;
    const GRAPHQL_INPUT_TYPE = Type::STRING;

    /**
     * @var SaveExpressionLanguage $expressionLanguage
     */
    protected $expressionLanguage;

    public function __construct(SaveExpressionLanguage $expressionLanguage)
    {
        $this->expressionLanguage = $expressionLanguage;
    }

    /**
     * {@inheritDoc}
     */
    public function extend(): string {
        return file_get_contents(__DIR__ . '/../../Resources/GraphQL/Schema/Field/' . static::getType() . '.graphql');
    }

    /**
     * {@inheritDoc}
     */
    static function getType(): string {
        return static::TYPE;
    }

    /**
     * {@inheritDoc}
     */
    public function GraphQLInputType(ContentTypeField $field) : ?string {
        return static::GRAPHQL_INPUT_TYPE;
    }

    /**
     * Used by validate method to check valid return types.
     *
     * @param \UniteCMS\CoreBundle\ContentType\ContentTypeField $field
     * @return array
     */
    protected function allowedReturnTypes(ContentTypeField $field) {
        return [$this->GraphQLInputType($field)];
    }

    /**
     * {@inheritDoc}
     */
    public function validateFieldDefinition(ContentType $contentType, ContentTypeField $field, ExecutionContextInterface $context) : void {

        // Validate return type.
        $allowedTypes = $this->allowedReturnTypes($field);
        if(!in_array($field->getReturnType(), $allowedTypes)) {
            $context
                ->buildViolation('Invalid GraphQL return type "{{ return_type }}" for field of type "{{ type }}". Please use on of [{{ allowed_return_types }}].')
                ->setParameter('{{ type }}', static::getType())
                ->setParameter('{{ return_type }}', $field->getReturnType())
                ->setParameter('{{ allowed_return_types }}', join(', ', $allowedTypes))
                ->addViolation();
            return;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getPublicSettings(ContentTypeField $field) : ?ArrayCollection {

        $defaultValue = $this->normalizeDefaultValue($field->getSettings()->get('default'));
        if(empty($defaultValue) && !empty($field->getSettings()->get('defaultExpression'))) {
            $defaultValue = $this->expressionLanguage->evaluate($field->getSettings()->get('defaultExpression'));
        }

        if(!empty($defaultValue)) {
            return new ArrayCollection([
                'default' => $defaultValue,
            ]);
        }

        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function resolveField(ContentInterface $content, ContentTypeField $field, FieldData $fieldData, array $args = []) {

        if($fieldData instanceof FieldDataList) {
            $resolve = [];
            foreach($fieldData->rows() as $rowData) {
                $resolve[] = $this->resolveRowData($content, $field, $rowData);
            }
            return $resolve;
        }

        return $this->resolveRowData($content, $field, $fieldData, $args);
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
        return $fieldData->resolveData('', ($field->isNonNull() || $field->isListOf()) ? '' : null);
    }

    /**
     * This method can be used normalize default input data during normalization.
     *
     * @param mixed $data
     * @return mixed $data
     */
    protected function normalizeDefaultValue($data) {
        return $data;
    }

    /**
     * {@inheritDoc}
     */
    public function normalizeInputData(ContentInterface $content, ContentTypeField $field, $inputData = null, int $rowDelta = null) : FieldData {

        $inputData = $inputData ?? $this->normalizeDefaultValue($field->getSettings()->get('default'));

        if(empty($inputData) && !empty($field->getSettings()->get('defaultExpression'))) {
            $inputData = $this->expressionLanguage->evaluate($field->getSettings()->get('defaultExpression'));
        }

        return new FieldData($inputData);
    }

    /**
     * {@inheritDoc}
     */
    public function validateFieldData(ContentInterface $content, ContentTypeField $field, ContextualValidatorInterface $validator, ExecutionContextInterface $context, FieldData $fieldData = null) : void {

        if($field->isRequired() && (empty($fieldData) || empty($fieldData->resolveData()))) {
            $validator->validate('', new NotBlank(), [$context->getGroup()]);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function queryOrderBy(ContentTypeField $field, array $sortInput) : ?BaseFieldOrderBy {
        return new DataFieldOrderBy($field->getId(), $sortInput['order']);
    }

    /**
     * {@inheritDoc}
     */
    public function queryComparison(ContentTypeField $field, array $whereInput) : ?BaseFieldComparison {

        switch ($this->GraphQLInputType($field)) {
            case Type::INT:
                $whereInput['value'] = array_map(function($value){ return (int)$value; }, $whereInput['value']);
                break;

            case Type::FLOAT:
                $whereInput['value'] = array_map(function($value){ return (float)$value; }, $whereInput['value']);
                break;

            case Type::BOOLEAN:
                $whereInput['value'] = array_map(function($value){ return filter_var($value, FILTER_VALIDATE_BOOLEAN); }, $whereInput['value']);
                break;
        }

        return new DataFieldComparison(
            $field->getId(),
            ContentCriteria::OPERATOR_MAP[$whereInput['operator']],
            ContentCriteria::castValue($whereInput['value'], $whereInput['cast'] ?? null),
            $whereInput['path'] ?? ['data']
        );
    }
}

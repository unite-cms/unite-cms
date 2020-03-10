<?php


namespace UniteCMS\CoreBundle\ContentType;

use GraphQL\Type\Definition\EnumType;
use GraphQL\Type\Definition\UnionType;
use Symfony\Component\Validator\Constraint;
use UniteCMS\CoreBundle\Field\FieldTypeManager;
use UniteCMS\CoreBundle\GraphQL\Util;
use UniteCMS\CoreBundle\Security\Voter\ContentFieldVoter;
use GraphQL\Type\Definition\FieldDefinition;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\Type;
use InvalidArgumentException;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\ParameterBag;
use UniteCMS\CoreBundle\GraphQL\SchemaManager;

use Symfony\Component\Validator\Constraints as Assert;
use UniteCMS\CoreBundle\Validator\Constraints\SaveExpression;

class ContentTypeField
{
    /**
     * @var string $id
     *
     * @Assert\NotBlank
     * @Assert\Regex(SchemaManager::GRAPHQL_NAME_REGEX)
     */
    protected $id;

    /**
     * @var string $id
     *
     * @Assert\NotBlank
     */
    protected $name;

    /**
     * @var string $type
     *
     * @Assert\NotBlank
     */
    protected $type;

    /**
     * @var bool
     */
    protected $nonNull;

    /**
     * @var bool
     */
    protected $required;

    /**
     * @var bool
     */
    protected $listOf;

    /**
     * @var string[]|null
     */
    protected $enumValues;

    /**
     * @var array[]|null
     */
    protected $unionTypes;

    /**
     * @var string
     *
     * @Assert\Regex(SchemaManager::GRAPHQL_NAME_REGEX)
     */
    protected $returnType;

    /**
     * @var array $directives
     */
    protected $directives = [];

    /**
     * @var ParameterBag $settings
     */
    protected $settings;

    /**
     * @var array
     * @Assert\Collection(fields={
     *   ContentFieldVoter::MUTATION = @Assert\NotBlank(),
     *   ContentFieldVoter::READ = @Assert\NotBlank(),
     *   ContentFieldVoter::UPDATE = @Assert\NotBlank()
     * })
     */
    protected $permissions;

    /**
     * @var Constraint[] $constraints
     */
    protected $constraints = [];

    public function __construct(string $id, string $name, string $type, array $settings = [], $nonNull = false, $required = false, $listOf = false, $enumValues = null, $unionTypes = null, $returnType = Type::STRING)
    {
        $this->permissions = [
            ContentFieldVoter::MUTATION => 'true',
            ContentFieldVoter::READ => 'true',
            ContentFieldVoter::UPDATE => 'true',
        ];
        $this->id = $id;
        $this->name = $name;
        $this->type = $type;
        $this->settings = new ParameterBag($settings);
        $this->nonNull = $nonNull;
        $this->required = $required;
        $this->listOf = $listOf;
        $this->enumValues = $enumValues;
        $this->unionTypes = $unionTypes;
        $this->returnType = $returnType;
    }

    /**
     * @param FieldDefinition $fieldDefinition
     * @return static|null
     */
    static function fromFieldDefinition(FieldDefinition $fieldDefinition) : ?self {

        // If this field definition has a @field directive.
        if($args = Util::typedDirectiveArgs($fieldDefinition->astNode, 'Field')) {

            // Find the actual inner type.
            $actualType = $fieldDefinition->getType();
            $nonNull = false;
            $listOf = false;
            $enumValues = null;
            $unionTypes = null;

            if($actualType instanceof NonNull) {
                $nonNull = true;
                $actualType = $actualType->getWrappedType();
            }

            if($actualType instanceof ListOfType) {

                if(!$actualType->getWrappedType() instanceof NonNull) {
                    throw new InvalidArgumentException(sprintf(
                        'ListOf types must always wrap a non-null type, however field "%1$s" contains type "%2$s" directly. Did you mean: %1$s: [%2$s!]?',
                        $actualType->name,
                        $actualType->getWrappedType()->name
                    ));
                }

                $listOf = true;
                $actualType = $actualType->getWrappedType(true);
            }

            if($actualType instanceof EnumType) {
                $enumValues = [];
                foreach($actualType->getValues() as $value) {
                    $enumValues[] = $value->value;
                }
            }

            if($actualType instanceof UnionType) {
                $unionTypes = [];
                foreach($actualType->getTypes() as $type) {
                    $unionTypes[$type->name] = [
                        'name' => $type->name,
                        'description' => $type->description,
                    ];
                }
            }

            $field = new self(
                $fieldDefinition->name,
                $fieldDefinition->description ?? $fieldDefinition->name,
                $args['type'],
                $args['settings'],
                $nonNull,
                false,
                $listOf,
                $enumValues,
                $unionTypes,
                $actualType->name
            );

            $field->applyDirectives(Util::getDirectives($fieldDefinition->astNode));

            return $field;
        }
        return null;
    }

    /**
     * @param $directives
     */
    public function applyDirectives($directives) {

        // Get all directives of this content type field.
        $this->directives = $directives;

        foreach ($this->directives as $directive) {

            // Special handle access directive.
            if($directive['name'] === 'access') {
                $this->setPermissions($directive['args']);
            }

            // Special handle valid directive.
            if($directive['name'] === 'valid') {
                $options = [
                    'expression' => $directive['args']['if'],
                ];
                if(!empty($directive['args']['message'])) {
                    $options['message'] = $directive['args']['message'];
                }
                if(!empty($directive['args']['groups'])) {
                    $options['groups'] = $directive['args']['groups'];
                }
                $this->addConstraint(new SaveExpression($options));
            }

            // Special handle required directive.
            if($directive['name'] === 'required') {
                $this->setRequired(true);
            }
        }
    }

    /**
     * @param FieldTypeManager $fieldTypeManager
     * @return string
     */
    public function printInputType(FieldTypeManager $fieldTypeManager) : string {

        // Union type input will be generated somewhere else.
        if(!empty($this->getUnionTypes())) {
            $inputType = sprintf('%sInput', $this->getReturnType());
        }

        // Normal types input types are defined by field type manager.
        else {
            $type = $fieldTypeManager->getFieldType($this->getType());
            $inputType = $type->GraphQLInputType($this);
        }

        if(empty($inputType)) {
            return '';
        }

        if($this->isListOf()) {
            $inputType = sprintf('[%s!]', $inputType);
        }

        return sprintf('%s: %s', $this->getId(), $inputType);
    }

    /**
     * @return string
     */
    public function getId() : string {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName() : string {
        $nameLines = explode("\n", $this->name);
        return $nameLines[0];
    }

    /**
     * @return string
     */
    public function getDescription() : ?string {
        $nameLines = explode("\n", $this->name);
        array_shift($nameLines);
        return join("\n", $nameLines);
    }

    /**
     * Get raw directives information.
     *
     * @return array
     */
    public function getDirectives() : array {
        return $this->directives;
    }

    /**
     * @return string
     */
    public function getType() : string {
        return $this->type;
    }

    /**
     * @return ParameterBag
     */
    public function getSettings() : ParameterBag {
        return $this->settings;
    }

    /**
     * @return bool
     */
    public function isNonNull() : bool {
        return $this->nonNull;
    }

    /**
     * @return bool
     */
    public function isRequired() : bool {
        return $this->nonNull || $this->required;
    }

    /**
     * @param bool $required
     * @return self
     */
    public function setRequired(bool $required) : self {
        $this->required = $required;
        return $this;
    }

    /**
     * @return bool
     */
    public function isListOf() : bool {
        return $this->listOf;
    }

    /**
     * @return array|null
     */
    public function getUnionTypes() : ?array {
        return $this->unionTypes;
    }

    /**
     * @return array|null
     */
    public function getEnumValues() : ?array {
        return $this->enumValues;
    }

    public function getReturnType() : string {
        return $this->returnType;
    }

    /**
     * @param array $permissions
     * @return self
     */
    public function setPermissions(array $permissions) : self {
        $this->permissions = array_merge($this->permissions, $permissions);
        return $this;
    }

    /**
     * @param string $permission
     * @return Expression
     */
    public function getPermission(string $permission) : Expression {
        return new Expression($this->permissions[$permission] ?? 'false');
    }

    /**
     * @param Constraint $constraint
     * @return $this
     */
    public function addConstraint(Constraint $constraint) : self {
        $this->constraints[] = $constraint;
        return $this;
    }

    /**
     * @return Constraint[]
     */
    public function getConstraints() : array {
        return $this->constraints;
    }

    public function toArray() : array {
        return [
            'id' => $this->getId(),
            'name' => $this->getName(),
            'type' => $this->getType(),
            'settings' => $this->getSettings()->all(),
            'directives' => $this->directives ?? [],
            'nonNull' => $this->isNonNull(),
            'required' => $this->isRequired(),
            'listOf' => $this->isListOf(),
            'enumValues' => $this->getEnumValues(),
            'unionTypes' => $this->getUnionTypes(),
            'returnType' => $this->getReturnType(),
        ];
    }

    /**
     * @param array $data
     * @return static
     */
    static function fromArray(array $data = []) {

        $field = new self(
            $data['id'],
            $data['name'],
            $data['type'],
            $data['settings'],
            $data['nonNull'],
            $data['required'],
            $data['listOf'],
            $data['enumValues'],
            $data['unionTypes'],
            $data['returnType']
        );
        $field->applyDirectives($data['directives'] ?? []);
        return $field;
    }
}

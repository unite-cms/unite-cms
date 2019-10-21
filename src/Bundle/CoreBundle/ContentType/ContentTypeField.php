<?php


namespace UniteCMS\CoreBundle\ContentType;

use GraphQL\Type\Definition\EnumType;
use GraphQL\Type\Definition\UnionType;
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
    protected $listOf;

    /**
     * @var string[]|null
     */
    protected $enumValues;

    /**
     * @var string[]|null
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
     */
    protected $permissions;

    public function __construct(string $id, string $type, array $settings = [], $nonNull = false, $listOf = false, $enumValues = null, $unionTypes = null, $returnType = Type::STRING)
    {
        $this->permissions = [
            ContentFieldVoter::READ => 'true',
            ContentFieldVoter::UPDATE => 'is_granted("ROLE_ADMIN")',
        ];
        $this->id = $id;
        $this->type = $type;
        $this->settings = new ParameterBag($settings);
        $this->nonNull = $nonNull;
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
                    $unionTypes[$type->name] = $type;
                }
            }

            $field = new self(
                $fieldDefinition->name,
                $args['type'],
                $args['settings'],
                $nonNull,
                $listOf,
                $enumValues,
                $unionTypes,
                $actualType->name
            );

            // Get all directives of this content type field.
            $field->directives = Util::getDirectives($fieldDefinition->astNode);

            // Special handle access directive.
            foreach ($field->directives as $directive) {
                if($directive['name'] === 'access') {
                    $field->setPermissions($directive['args']);
                }
            }

            return $field;
        }
        return null;
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

        if($this->isNonNull()) {
            $inputType .= '!';
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
    public function isListOf() : bool {
        return $this->listOf;
    }

    /**
     * @return \UniteCMS\CoreBundle\ContentType\ContentType[]|null
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
}

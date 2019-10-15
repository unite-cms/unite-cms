<?php


namespace UniteCMS\CoreBundle\ContentType;

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

class ContentTypeField
{
    /**
     * @var string $id
     */
    protected $id;

    /**
     * @var string $type
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
     * @var string
     */
    protected $returnType;

    /**
     * @var ParameterBag $settings
     */
    protected $settings;

    /**
     * @var array
     */
    protected $permissions;

    public function __construct(string $id, string $type, array $settings = [], $nonNull = false, $listOf = false, $returnType = Type::STRING)
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
        $this->returnType = $returnType;
    }

    /**
     * @param FieldDefinition $fieldDefinition
     * @return static|null
     */
    static function fromFieldDefinition(FieldDefinition $fieldDefinition) : ?self {

        // If this field definition has a @field directive.
        if($args = Util::fieldDirectiveArgs($fieldDefinition->astNode)) {

            // Find the actual inner type.
            $actualType = $fieldDefinition->getType();
            $nonNull = false;
            $listOf = false;

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

            $field = new self(
                $fieldDefinition->name,
                $args['type'],
                $args['settings'],
                $nonNull,
                $listOf,
                $actualType->name
            );

            if($args = Util::directiveArgs($fieldDefinition, 'access')) {
                $field->setPermissions($args);
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
        $type = $fieldTypeManager->getFieldType($this->getType());
        $inputType = $type->GraphQLInputType($this);

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

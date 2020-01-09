<?php


namespace UniteCMS\CoreBundle\ContentType;

use GraphQL\Error\Error;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\Constraint;
use UniteCMS\CoreBundle\Field\FieldTypeManager;
use UniteCMS\CoreBundle\Field\Types\ChoiceType;
use UniteCMS\CoreBundle\GraphQL\Util;
use UniteCMS\CoreBundle\Security\Voter\ContentFieldVoter;
use UniteCMS\CoreBundle\Security\Voter\ContentVoter;
use GraphQL\Type\Definition\ObjectType;
use Symfony\Component\ExpressionLanguage\Expression;
use UniteCMS\CoreBundle\GraphQL\SchemaManager;
use Symfony\Component\Validator\Constraints as Assert;
use UniteCMS\CoreBundle\Validator\Constraints as UniteAssert;

class ContentType
{
    /**
     * @Assert\NotBlank
     * @Assert\Regex(SchemaManager::GRAPHQL_NAME_REGEX)
     * @var string
     */
    protected $id;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var bool $translatable
     */
    protected $translatable = false;

    /**
     * @var array $directives
     */
    protected $directives = [];

    /**
     * @var ContentTypeField[] $fields
     * @Assert\Valid
     * @UniteAssert\ContentTypeField
     */
    protected $fields = [];

    /**
     * @var Constraint[] $constraints
     */
    protected $constraints = [];

    /**
     * @var ContentTypeWebhook[] $webhooks
     */
    protected $webhooks = [];

    /**
     * @var array
     * @Assert\Collection(fields={
     *   ContentVoter::QUERY = @Assert\NotBlank(),
     *   ContentVoter::READ = @Assert\NotBlank(),
     *   ContentVoter::COUNT = @Assert\NotBlank(),
     *   ContentVoter::MUTATION = @Assert\NotBlank(),
     *   ContentVoter::CREATE = @Assert\NotBlank(),
     *   ContentVoter::UPDATE = @Assert\NotBlank(),
     *   ContentVoter::DELETE = @Assert\NotBlank(),
     *   ContentVoter::PERMANENT_DELETE = @Assert\NotBlank()
     * })
     */
    protected $permissions = [];

    public function __construct(string $id, string $name, string $defaultPermission)
    {
        $this->permissions = [
            ContentVoter::QUERY => 'true',
            ContentVoter::READ => 'true',
            ContentVoter::COUNT => 'true',
            ContentVoter::MUTATION => $defaultPermission,
            ContentVoter::CREATE => $defaultPermission,
            ContentVoter::UPDATE => $defaultPermission,
            ContentVoter::DELETE => $defaultPermission,
            ContentVoter::PERMANENT_DELETE => $defaultPermission,
        ];
        $this->id = $id;
        $this->name = $name;
    }

    /**
     * @param ObjectType $type
     * @param string $defaultPermission
     *
     * @return static
     * @throws Error
     */
    static function fromObjectType(ObjectType $type, string $defaultPermission) : self {
        $contentType = new static($type->name, $type->description ?? $type->name, $defaultPermission);

        // Get all directives of this content type.
        $contentType->directives = Util::getDirectives($type->astNode);

        foreach ($contentType->directives as $directive) {

            // Special handle access directive.
            if($directive['name'] === 'access') {
                $contentType->setPermissions($directive['args']);
            }

            // Special handle valid directive.
            if($directive['name'] === 'valid') {
                $contentType->addConstraintFromDirective($directive);
            }

            // Special handle webhook directive.
            if($directive['name'] === 'webhook') {
                $contentType->addWebhook(new ContentTypeWebhook($directive['args']['if'], $directive['args']['url'], $directive['args']['groups']));
            }
        }

        foreach($type->getInterfaces() as $interface) {

            // Make this content type translatable if interface was found.
            if($interface->name === 'UniteTranslatableContent') {
                $contentType->setTranslatable(true);
            }
        }

        foreach($type->getFields() as $field) {
            if($contentTypeField = ContentTypeField::fromFieldDefinition($field)) {
                $contentType->registerField($contentTypeField);
            }
        }

        if($contentType->isTranslatable() && (!$contentType->getField('locale') || $contentType->getField('locale')->getType() !== ChoiceType::getType())) {
            throw new Error('Types that implement "UniteContentInterface" must have a "locale" Choice Type.');
        }

        return $contentType;
    }

    /**
     * @return string
     */
    public function printResultType() : string {
        return sprintf('type %1$sResult implements UniteContentResult {
            total: Int!
            result: [%1$s!]
        }', $this->getId());
    }

    /**
     * @param FieldTypeManager $fieldTypeManager
     * @param AuthorizationCheckerInterface $checker
     *
     * @return string
     */
    public function printInputType(FieldTypeManager $fieldTypeManager, AuthorizationCheckerInterface $checker) : string {
        $inputFields = '';

        if($this->canHaveInput($fieldTypeManager)) {

            if($this->isTranslatable()) {
                $inputFields .= "_translate: ID\n";
            }

            foreach ($this->getFields() as $field) {
                if ($checker->isGranted(ContentFieldVoter::MUTATION, $field)) {
                    $inputFields .= $field->printInputType($fieldTypeManager) . "\n";
                }
            }
        }

        return $inputFields ? sprintf('input %sInput { %s }', $this->getId(), $inputFields) : '';
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
     * @return bool
     */
    public function isTranslatable(): bool
    {
        return $this->translatable;
    }

    /**
     * @param bool $translatable
     * @return self
     */
    public function setTranslatable(bool $translatable): ContentType
    {
        $this->translatable = $translatable;
        return $this;
    }

    /**
     * @param ContentTypeField $field
     *
     * @return $this
     */
    public function registerField(ContentTypeField $field) : self {
        $this->fields[$field->getId()] = $field;
        return $this;
    }

    /**
     * @return ContentTypeField[]
     */
    public function getFields() : array {
        return $this->fields;
    }

    /**
     * @param FieldTypeManager $fieldTypeManager
     * @return bool
     */
    public function canHaveInput(FieldTypeManager $fieldTypeManager) : bool {

        if($this->isTranslatable()) {
            return true;
        }

        if(count($this->fields) === 0) {
            return false;
        }

        foreach($this->fields as $field) {
            if(!empty($fieldTypeManager->getFieldType($field->getType())->GraphQLInputType($field))) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $id
     * @return ContentTypeField|null
     */
    public function getField(string $id) : ?ContentTypeField {
        return $this->fields[$id] ?? null;
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
     * @param array $directive
     * @return $this
     */
    public function addConstraintFromDirective(array $directive) : self {

        $options = [
            'expression' => $directive['args']['if'],
        ];
        if(!empty($directive['args']['message'])) {
            $options['message'] = $directive['args']['message'];
        }
        if(!empty($directive['args']['groups'])) {
            $options['groups'] = $directive['args']['groups'];
        }

        return $this->addConstraint(new UniteAssert\SaveExpression($options));
    }

    /**
     * @return Constraint[]
     */
    public function getConstraints() : array {
        return $this->constraints;
    }

    /**
     * @param ContentTypeWebhook $webhook
     * @return $this
     */
    public function addWebhook(ContentTypeWebhook $webhook) : self {
        $this->webhooks[] = $webhook;
        return $this;
    }

    /**
     * @return ContentTypeWebhook[]
     */
    public function getWebhooks() : array {
        return $this->webhooks;
    }
}

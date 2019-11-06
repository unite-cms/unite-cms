<?php


namespace UniteCMS\CoreBundle\ContentType;

use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\Constraint;
use UniteCMS\CoreBundle\Field\FieldTypeManager;
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
     *   ContentVoter::MUTATION = @Assert\NotBlank(),
     *   ContentVoter::CREATE = @Assert\NotBlank(),
     *   ContentVoter::READ = @Assert\NotBlank(),
     *   ContentVoter::UPDATE = @Assert\NotBlank(),
     *   ContentVoter::DELETE = @Assert\NotBlank()
     * })
     */
    protected $permissions = [];

    public function __construct(string $id)
    {
        $this->permissions = [
            ContentVoter::QUERY => 'true',
            ContentVoter::MUTATION => 'has_role("ROLE_ADMIN")',
            ContentVoter::CREATE => 'has_role("ROLE_ADMIN")',
            ContentVoter::READ => 'true',
            ContentVoter::UPDATE => 'has_role("ROLE_ADMIN")',
            ContentVoter::DELETE => 'has_role("ROLE_ADMIN")',
        ];
        $this->id = $id;
    }

    /**
     * @param ObjectType $type
     * @return static
     */
    static function fromObjectType(ObjectType $type) : self {
        $contentType = new static($type->name);

        // Get all directives of this content type.
        $contentType->directives = Util::getDirectives($type->astNode);

        foreach ($contentType->directives as $directive) {

            // Special handle access directive.
            if($directive['name'] === 'access') {
                $contentType->setPermissions($directive['args']);
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
                $contentType->addConstraint(new UniteAssert\SaveExpression($options));
            }

            // Special handle webhook directive.
            if($directive['name'] === 'webhook') {
                $contentType->addWebhook(new ContentTypeWebhook($directive['args']['if'], $directive['args']['url'], $directive['args']['groups']));
            }
        }

        foreach($type->getFields() as $field) {
            if($contentTypeField = ContentTypeField::fromFieldDefinition($field)) {
                $contentType->registerField($contentTypeField);
            }
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

        foreach($this->getFields() as $field) {
            if($checker->isGranted(ContentFieldVoter::MUTATION, $field)) {
                $inputFields .= $field->printInputType($fieldTypeManager)."\n";
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
     * Get raw directives information.
     *
     * @return array
     */
    public function getDirectives() : array {
        return $this->directives;
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

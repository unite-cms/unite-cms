<?php


namespace UniteCMS\AdminBundle\AdminView;


use GraphQL\Error\SyntaxError;
use GraphQL\Language\AST\FragmentDefinitionNode;
use GraphQL\Language\Parser;
use Symfony\Component\Security\Core\Security;
use UniteCMS\AdminBundle\AdminView\Types\EmbeddedType;
use UniteCMS\AdminBundle\AdminView\Types\SettingsType;
use UniteCMS\AdminBundle\AdminView\Types\TableType;
use UniteCMS\AdminBundle\Exception\InvalidAdminViewType;
use UniteCMS\CoreBundle\ContentType\ContentType;
use UniteCMS\CoreBundle\ContentType\ContentTypeManager;
use UniteCMS\CoreBundle\Domain\Domain;
use UniteCMS\CoreBundle\Domain\DomainManager;
use UniteCMS\CoreBundle\Expression\SaveExpressionLanguage;
use UniteCMS\CoreBundle\Field\FieldTypeManager;
use UniteCMS\CoreBundle\GraphQL\Util;
use UniteCMS\CoreBundle\Log\LoggerInterface;
use UniteCMS\CoreBundle\Security\Voter\ContentVoter;

class AdminViewTypeManager
{
    const TYPE_CONTENT = 'content';
    const TYPE_USER = 'user';
    const TYPE_SINGLE_CONTENT = 'single_content';
    const TYPE_EMBEDDED = 'embedded';

    /**
     * @var AdminViewTypeInterface[]
     */
    protected $adminViewTypes = [];

    /**
     * @var AdminFieldConfiguratorInterface[]
     */
    protected $adminViewFieldConfigurators = [];

    /**
     * @var \UniteCMS\CoreBundle\Domain\DomainManager
     */
    protected $domainManager;

    /**
     * @var FieldTypeManager $fieldTypeManager
     */
    protected $fieldTypeManager;

    /**
     * @var Security $security
     */
    protected $security;

    /**
     * @var SaveExpressionLanguage $expressionLanguage
     */
    protected $expressionLanguage;

    public function __construct(DomainManager $domainManager, FieldTypeManager $fieldTypeManager, Security $security, SaveExpressionLanguage $expressionLanguage)
    {
        $this->domainManager = $domainManager;
        $this->fieldTypeManager = $fieldTypeManager;
        $this->security = $security;
        $this->expressionLanguage = $expressionLanguage;
    }

    /**
     * @param AdminViewTypeInterface $adminViewType
     * @return self
     */
    public function registerAdminViewType(AdminViewTypeInterface $adminViewType) : self {
        $this->adminViewTypes[$adminViewType::getType()] = $adminViewType;
        return $this;
    }

    /**
     * @param AdminFieldConfiguratorInterface $adminViewFieldConfigurator
     *
     * @return self
     */
    public function registerAdminViewFieldConfigurator(
        AdminFieldConfiguratorInterface $adminViewFieldConfigurator) : self {
        $this->adminViewFieldConfigurators[] = $adminViewFieldConfigurator;
        return $this;
    }

    /**
     * @param ContentTypeManager $contentTypeManager
     * @param string $contentType
     *
     * @return string
     */
    protected function mapContentTypeCategory(ContentTypeManager $contentTypeManager, string $contentType) : string {
        if($contentTypeManager->getContentType($contentType)) {
            return self::TYPE_CONTENT;
        }

        else if($contentTypeManager->getUserType($contentType)) {
            return self::TYPE_USER;
        }

        else if($contentTypeManager->getSingleContentType($contentType)) {
            return self::TYPE_SINGLE_CONTENT;
        }

        else if($contentTypeManager->getEmbeddedContentType($contentType)) {
            return self::TYPE_EMBEDDED;
        }

        else {
            throw new InvalidAdminViewType();
        }
    }

    /**
     * @param string $category
     * @return AdminViewTypeInterface
     */
    protected function mapDefaultAdminViewType(string $category) : AdminViewTypeInterface {
        switch ($category) {
            case self::TYPE_CONTENT :
            case self::TYPE_USER :
                return $this->adminViewTypes[TableType::getType()];

            case self::TYPE_SINGLE_CONTENT :
                return $this->adminViewTypes[SettingsType::getType()];

            case self::TYPE_EMBEDDED :
                return $this->adminViewTypes[EmbeddedType::getType()];

            default:
                throw new InvalidAdminViewType();
        }
    }

    /**
     * @param AdminView $adminView
     * @param ContentType $contentType
     *
     * @return AdminView
     */
    protected function mapFieldConfig(AdminView $adminView, ContentType $contentType) : AdminView {
        foreach($adminView->getFields() as $field) {
            if($ctField = $contentType->getField($field->getName())) {
                if($config = $this->fieldTypeManager->getFieldType($ctField->getType())->getPublicSettings($ctField)) {
                    $field->setConfig($config);
                }

            }
        }

        return $adminView;
    }

    /**
     * @param AdminView $adminView
     * @param ContentType $contentType
     *
     * @return AdminView
     */
    protected function configureFields(AdminView $adminView, ContentType $contentType) : AdminView {
        foreach($adminView->getFields() as $field) {
            foreach($this->adminViewFieldConfigurators as $adminViewFieldConfigurator) {
                $adminViewFieldConfigurator->configureField($field, $adminView, $contentType);
            }
        }

        return $adminView;
    }

    /**
     * @param AdminView $adminView
     * @param ContentType $contentType
     *
     * @return AdminView
     */
    protected function mapPermissions(AdminView $adminView, ContentType $contentType) : AdminView {
        $permissions = [];
        foreach(ContentVoter::LIST_PERMISSIONS as $permission) {
            $permissions[$permission] = $this->security->isGranted($permission, $contentType);
        }
        $adminView->setPermissions($permissions);
        return $adminView;
    }

    /**
     * @param Domain $domain
     *
     * @return AdminView[]
     */
    public function getAdminViews(Domain $domain = null) : array {

        if(!$domain) {
            $domain = $this->domainManager->current();
        }

        $usedContentTypes = [];
        $adminViews = [];

        try {
            $schema = Parser::parse(join("\n", $domain->getCompleteSchema()));
        } catch(SyntaxError $e) {
            $domain->log(LoggerInterface::ERROR, sprintf('Could not parse schema for @adminView fragments, because of SyntaxError: %s', $e->getMessage()));
            return [];
        }

        // Check all adminView fragments and create adminViews out of them.
        foreach($schema->definitions as $definition) {
            if($definition instanceof FragmentDefinitionNode) {

                if(!($directive = Util::typedDirectiveArgs($definition, 'AdminView'))) {
                    continue;
                }

                if(!($adminViewType = $this->adminViewTypes[$directive['type']] ?? null)) {
                    continue;
                }

                // If the user is not allowed to see this adminView.
                if(!empty($directive['args']['if']) && !(bool)$this->expressionLanguage->evaluate($directive['args']['if'])) {
                    continue;
                }

                // AdminView basic infos.
                $id = $definition->typeCondition->name->value;
                $category = $this->mapContentTypeCategory($domain->getContentTypeManager(), $id);
                $contentType = $domain->getContentTypeManager()->getAnyType($id);

                // If the user is not allowed to query this content type.
                if(!$this->security->isGranted(ContentVoter::QUERY, $contentType)) {
                    continue;
                }

                // Ask the admin view type to create a new AdminView
                $adminView = $adminViewType->createView($category, $contentType, $definition, $directive);

                // Check list permissions for this admin view.
                $this->mapPermissions($adminView, $contentType);

                // Enrich content type fields with field settings.
                $this->mapFieldConfig($adminView, $contentType);

                // Allow admin field configurators to alter field config.
                $this->configureFields($adminView, $contentType);

                $adminViews[] = $adminView;
                $usedContentTypes[] = $contentType->getId();
            }
        }

        // Add a fallback adminView for all list types.
        foreach($domain->getContentTypeManager()->getAllTypes() as $contentType) {
            if(!in_array($contentType->getId(), $usedContentTypes)) {

                // TODO: Do we need to support union types?
                if($domain->getContentTypeManager()->getUnionContentType($contentType->getId())) {
                    continue;
                }

                $category = $this->mapContentTypeCategory($domain->getContentTypeManager(), $contentType->getId());

                // If the user is not allowed to query this content type.
                if(!$this->security->isGranted(ContentVoter::QUERY, $contentType)) {
                    continue;
                }

                // Ask the admin view type to create a new AdminView
                $adminView = $this
                    ->mapDefaultAdminViewType($category)
                    ->createView($category, $contentType);

                // Check list permissions for this admin view.
                $this->mapPermissions($adminView, $contentType);

                // Enrich content type fields with field settings.
                $this->mapFieldConfig($adminView, $contentType);

                // Allow admin field configurators to alter field config.
                $this->configureFields($adminView, $contentType);

                $adminViews[] = $adminView;
            }
        }

        return $adminViews;
    }
}

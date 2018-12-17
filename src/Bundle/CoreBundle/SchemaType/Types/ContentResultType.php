<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 14.10.17
 * Time: 12:29
 */

namespace UniteCMS\CoreBundle\SchemaType\Types;

use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Knp\Component\Pager\Pagination\AbstractPagination;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use UniteCMS\CoreBundle\Entity\ContentType;
use UniteCMS\CoreBundle\Entity\Domain;
use UniteCMS\CoreBundle\SchemaType\IdentifierNormalizer;
use UniteCMS\CoreBundle\Security\Voter\ContentVoter;
use UniteCMS\CoreBundle\Service\UniteCMSManager;
use UniteCMS\CoreBundle\SchemaType\SchemaTypeManager;

class ContentResultType extends AbstractType
{

    /**
     * @var SchemaTypeManager $schemaTypeManager
     */
    private $schemaTypeManager;

    /**
     * @var AuthorizationChecker $authorizationChecker
     */
    private $authorizationChecker;

    /**
     * @var Domain $domain
     */
    private $domain;

    /**
     * @var ContentType $contentType
     */
    private $contentType;

    /**
     * @var string $contentSchemaType
     */
    private $contentSchemaType;

    /**
     * @var int $nestingLevel
     */
    private $nestingLevel;

    public function __construct(
        SchemaTypeManager $schemaTypeManager,
        AuthorizationChecker $authorizationChecker,
        UniteCMSManager $uniteCMSManager = null,
        Domain $domain = null,
        ContentType $contentType = null,
        $contentSchemaType = 'ContentInterface',
        $nestingLevel = 0
    ) {
        $this->schemaTypeManager = $schemaTypeManager;
        $this->authorizationChecker = $authorizationChecker;
        $this->domain = $domain ? $domain : $uniteCMSManager->getDomain();
        $this->contentType = $contentType;
        $this->contentSchemaType = $contentSchemaType;
        $this->nestingLevel = $nestingLevel;
        parent::__construct();
    }

    /**
     * Define all interfaces, this type implements.
     *
     * @return array
     */
    protected function interfaces() {
        return [ $this->schemaTypeManager->getSchemaType('ContentResultInterface') ];
    }

    /**
     * Define all fields of this type.
     *
     * @return array
     */
    protected function fields()
    {
        $fields = [
            'result' => Type::listOf($this->schemaTypeManager->getSchemaType($this->contentSchemaType, $this->domain, $this->nestingLevel)),
            'total' => Type::int(),
            'page' => Type::int(),
        ];

        // Create or get permissions type for this content type.
        if($this->contentType) {
            $permissionsTypeName = IdentifierNormalizer::graphQLType($this->contentType, 'ContentResultPermissions');
            if (!$this->schemaTypeManager->hasSchemaType($permissionsTypeName)) {
                $this->schemaTypeManager->registerSchemaType(
                    new PermissionsType(ContentVoter::BUNDLE_PERMISSIONS, $permissionsTypeName)
                );
            }

            $fields['_permissions'] = $this->schemaTypeManager->getSchemaType($permissionsTypeName);
        }

        return $fields;
    }

    /**
     * Resolve fields for this type.
     * Returns the object or scalar value for the field, define in $info.
     *
     * @param mixed $value
     * @param array $args
     * @param $context
     * @param ResolveInfo $info
     *
     * @return mixed
     */
    protected function resolveField($value, array $args, $context, ResolveInfo $info)
    {

        if (!$value instanceof AbstractPagination) {
            throw new \InvalidArgumentException('Value must be instance of '.AbstractPagination::class.'.');
        }

        switch ($info->fieldName) {
            case 'result':
                $items = [];

                /**
                 * @var \UniteCMS\CoreBundle\Entity\Content $item
                 */
                foreach ($value->getItems() as $item) {
                    if ($this->authorizationChecker->isGranted(ContentVoter::VIEW, $item)) {
                        $items[] = $item;

                        // Create content schema type for current domain.
                        $type = IdentifierNormalizer::graphQLType($item->getContentType());
                        $this->schemaTypeManager->getSchemaType($type, $this->domain);
                    }
                }

                return $items;
            case 'total':
                $total = $value->getTotalItemCount();

                // Reduce the total number of items by the number of items we don't have access to. This will only be
                // correct, if we have not more than $limit items, but it is better than nothing.
                foreach ($value->getItems() as $item) {
                    if (!$this->authorizationChecker->isGranted(ContentVoter::VIEW, $item)) {
                        $total--;
                    }
                }

                return $total;
            case 'page':
                return $value->getCurrentPageNumber();

            case '_permissions':
                $permissions = [];

                if($this->contentType) {
                    foreach (ContentVoter::BUNDLE_PERMISSIONS as $permission) {
                        $permissions[$permission] = $this->authorizationChecker->isGranted(
                            $permission,
                            $this->contentType
                        );
                    }
                }

                return $permissions;

            default:
                return null;
        }
    }
}

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
use UniteCMS\CoreBundle\Entity\DomainMemberType;
use UniteCMS\CoreBundle\Entity\Fieldable;
use UniteCMS\CoreBundle\Entity\FieldableContent;
use UniteCMS\CoreBundle\SchemaType\IdentifierNormalizer;
use UniteCMS\CoreBundle\Security\Voter\ContentVoter;
use UniteCMS\CoreBundle\Security\Voter\DomainMemberVoter;
use UniteCMS\CoreBundle\Service\UniteCMSManager;
use UniteCMS\CoreBundle\SchemaType\SchemaTypeManager;

class FieldableContentResultType extends AbstractType
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
     * @var Fieldable $fieldable
     */
    private $fieldable;

    /**
     * @var string $contentSchemaType
     */
    private $contentSchemaType;

    /**
     * @var string $permissionTypeName
     */
    private $permissionTypeName;

    public function __construct(
        SchemaTypeManager $schemaTypeManager,
        AuthorizationChecker $authorizationChecker,
        UniteCMSManager $uniteCMSManager = null,
        Domain $domain = null,
        Fieldable $fieldable = null,
        $contentSchemaType = 'FieldableContentInterface'
    ) {
        $this->schemaTypeManager = $schemaTypeManager;
        $this->authorizationChecker = $authorizationChecker;
        $this->domain = $domain ? $domain : $uniteCMSManager->getDomain();
        $this->fieldable = $fieldable;
        $this->contentSchemaType = $contentSchemaType;

        // Create or get permissions type for this content type.
        $this->permissionTypeName = null;

        if($this->fieldable) {
            $bundlePermissions = [];

            if ($this->fieldable instanceof ContentType) {
                $this->permissionTypeName = 'ContentResultPermissions';
                $bundlePermissions = ContentVoter::BUNDLE_PERMISSIONS;
            } else {
                if ($this->fieldable instanceof DomainMemberType) {
                    $this->permissionTypeName = 'MemberResultPermissions';
                    $bundlePermissions = DomainMemberVoter::BUNDLE_PERMISSIONS;
                }
            }

            $this->permissionTypeName = IdentifierNormalizer::graphQLType($this->fieldable, $this->permissionTypeName);
            if (!$this->schemaTypeManager->hasSchemaType($this->permissionTypeName)) {
                $this->schemaTypeManager->registerSchemaType(
                    new PermissionsType($bundlePermissions, $this->permissionTypeName)
                );
            }
        }

        parent::__construct();
    }

    /**
     * Define all fields of this type.
     *
     * @return array
     */
    protected function fields()
    {
        return array_merge([
            'result' => Type::listOf($this->schemaTypeManager->getSchemaType($this->contentSchemaType, $this->domain)),
            'total' => Type::int(),
            'page' => Type::int(),
        ], $this->permissionTypeName ? [
            '_permissions' => $this->schemaTypeManager->getSchemaType($this->permissionTypeName),
        ] : []);
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

        if($this->fieldable instanceof DomainMemberType) {
            $viewPermission = DomainMemberVoter::VIEW;
            $bundlePermissions = DomainMemberVoter::BUNDLE_PERMISSIONS;
        } else {
            $viewPermission = ContentVoter::VIEW;
            $bundlePermissions = ContentVoter::BUNDLE_PERMISSIONS;
        }

        switch ($info->fieldName) {
            case 'result':
                $items = [];

                foreach ($value->getItems() as $item) {

                    $val = $item;
                    if(is_array($val)) {
                        $val = $val[0];
                    }

                    if ($this->authorizationChecker->isGranted($viewPermission, $val)) {
                        $items[] = $item;

                        // Create content schema type for current domain.
                        $type = IdentifierNormalizer::graphQLType($val->getEntity());
                        $this->schemaTypeManager->getSchemaType($type, $this->domain);
                    }
                }

                return $items;
            case 'total':
                $total = $value->getTotalItemCount();

                // Reduce the total number of items by the number of items we don't have access to. This will only be
                // correct, if we have not more than $limit items, but it is better than nothing.
                foreach ($value->getItems() as $item) {

                    $val = $item;
                    if(is_array($val)) {
                        $val = $val[0];
                    }

                    if (!$this->authorizationChecker->isGranted($viewPermission, $val)) {
                        $total--;
                    }
                }

                return $total;
            case 'page':
                return $value->getCurrentPageNumber();

            case '_permissions':
                $permissions = [];

                if($this->fieldable) {
                    foreach ($bundlePermissions as $permission) {
                        $permissions[$permission] = $this->authorizationChecker->isGranted(
                            $permission,
                            $this->fieldable
                        );
                    }
                }

                return $permissions;

            default:
                return null;
        }
    }
}

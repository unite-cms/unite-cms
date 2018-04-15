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
use UniteCMS\CoreBundle\Entity\Domain;
use UniteCMS\CoreBundle\Security\ContentVoter;
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
     * @var string $contentSchemaType
     */
    private $contentSchemaType;

    public function __construct(
        SchemaTypeManager $schemaTypeManager,
        AuthorizationChecker $authorizationChecker,
        UniteCMSManager $uniteCMSManager = null,
        Domain $domain = null,
        $contentSchemaType = 'ContentInterface'
    ) {
        $this->schemaTypeManager = $schemaTypeManager;
        $this->authorizationChecker = $authorizationChecker;
        $this->domain = $domain ? $domain : $uniteCMSManager->getDomain();
        $this->contentSchemaType = $contentSchemaType;
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
        return [
            'result' => Type::listOf($this->schemaTypeManager->getSchemaType($this->contentSchemaType, $this->domain)),
            'total' => Type::int(),
            'page' => Type::int(),
        ];
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
                        $type = ucfirst($item->getContentType()->getIdentifier()).'Content';
                        $this->schemaTypeManager->getSchemaType($type, $this->domain);
                    }
                }

                return $items;
            case 'total':
                return $value->getTotalItemCount();
            case 'page':
                return $value->getCurrentPageNumber();
            default:
                return null;
        }
    }
}

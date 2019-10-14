<?php


namespace UniteCMS\CoreBundle\GraphQL\Resolver;

use UniteCMS\CoreBundle\Content\ContentInterface;
use UniteCMS\CoreBundle\Content\ContentResultInterface;
use UniteCMS\CoreBundle\Domain\DomainManager;
use UniteCMS\CoreBundle\Field\FieldTypeManager;
use GraphQL\Language\AST\ObjectTypeDefinitionNode;
use GraphQL\Type\Definition\ResolveInfo;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class ContentResolver implements FieldResolverInterface
{
    /**
     * @var FieldTypeManager $fieldTypeManager
     */
    protected $fieldTypeManager;

    /**
     * @var AuthorizationCheckerInterface $authorizationChecker
     */
    protected $authorizationChecker;

    /**
     * @var DomainManager $domainManager
     */
    protected $domainManager;

    public function __construct(FieldTypeManager $fieldTypeManager, AuthorizationCheckerInterface $authorizationChecker, DomainManager $domainManager)
    {
        $this->fieldTypeManager = $fieldTypeManager;
        $this->authorizationChecker = $authorizationChecker;
        $this->domainManager = $domainManager;
    }

    /**
     * @inheritDoc
     */
    public function supports(string $typeName, ObjectTypeDefinitionNode $typeDefinitionNode): bool {
        foreach($typeDefinitionNode->interfaces as $interface) {
            if($interface->name->value === 'UniteContent') {
                return true;
            }

            if($interface->name->value === 'UniteEmbeddedContent') {
                return true;
            }

            if($interface->name->value === 'UniteContentResult') {
                return true;
            }
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function resolve($value, $args, $context, ResolveInfo $info) {

        if($value instanceof ContentResultInterface) {
            switch ($info->fieldName) {
                case 'total':
                    return $value->getTotal();
                case 'result';
                    return $value->getResult();
            }
        }

        else if($value instanceof ContentInterface) {

            $contentTypeManager = $this->domainManager->current()->getContentTypeManager();
            $contentType = $contentTypeManager->getContentType($value->getType()) ?? $contentTypeManager->getEmbeddedContentType($value->getType());

            if($contentType) {
                switch ($info->fieldName) {
                    case 'id':
                        return $value->getId();
                    default:

                        if(!$field = $contentType->getField($info->fieldName)) {
                            return null;
                        }

                        return $this->fieldTypeManager
                            ->getFieldType($field->getType())
                            ->resolveField($info->fieldName, $value, $field);
                }
            }
        }

        return null;
    }
}

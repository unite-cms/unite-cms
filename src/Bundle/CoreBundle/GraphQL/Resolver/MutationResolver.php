<?php


namespace UniteCMS\CoreBundle\GraphQL\Resolver;

use GraphQL\Language\AST\ObjectTypeDefinitionNode;
use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\WrappingType;
use InvalidArgumentException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use UniteCMS\CoreBundle\Content\ContentInterface;
use UniteCMS\CoreBundle\Content\ContentManagerInterface;
use UniteCMS\CoreBundle\Content\FieldDataList;
use UniteCMS\CoreBundle\ContentType\ContentTypeField;
use UniteCMS\CoreBundle\Domain\Domain;
use UniteCMS\CoreBundle\Domain\DomainManager;
use UniteCMS\CoreBundle\Field\FieldTypeManager;
use UniteCMS\CoreBundle\Security\Voter\ContentVoter;

class MutationResolver implements FieldResolverInterface
{

    /**
     * @var AuthorizationCheckerInterface $authorizationChecker
     */
    protected $authorizationChecker;

    /**
     * @var DomainManager $domainManager
     */
    protected $domainManager;

    /**
     * @var FieldTypeManager $fieldTypeManager
     */
    protected $fieldTypeManager;

    public function __construct(AuthorizationCheckerInterface $authorizationChecker, DomainManager $domainManager, FieldTypeManager $fieldTypeManager)
    {
        $this->authorizationChecker = $authorizationChecker;
        $this->domainManager = $domainManager;
        $this->fieldTypeManager = $fieldTypeManager;
    }

    /**
     * @inheritDoc
     */
    public function supports(string $typeName, ObjectTypeDefinitionNode $typeDefinitionNode): bool {
        return $typeName === 'Mutation';
    }

    /**
     * @inheritDoc
     */
    public function resolve($value, $args, $context, ResolveInfo $info) {

        $actualType = $info->returnType;

        if($actualType instanceof WrappingType) {
            $actualType = $actualType->getWrappedType(true);
        }

        /**
         * @var InterfaceType $contentInterface
         */
        $contentInterface = $info->schema->getType('UniteContent');

        /**
         * @var InterfaceType $userInterface
         */
        $userInterface = $info->schema->getType('UniteUser');

        if(!$actualType instanceof ObjectType) {
            return null;
        }

        $fieldNameParts = preg_split('/(?=[A-Z])/',$info->fieldName);
        if(count($fieldNameParts) !== 2) {
            return null;
        }

        $field = $fieldNameParts[0];
        $type = $fieldNameParts[1];

        $domain = $this->domainManager->current();
        $contentManager = null;

        if($actualType->implementsInterface($contentInterface)) {
            $contentManager = $domain->getContentManager();
        }

        else if($actualType->implementsInterface($userInterface)) {
            $contentManager = $domain->getUserManager();
        }

        else {
            return null;
        }

        switch ($field) {
            case 'create':
                $this->contentOrException($domain, $contentManager, $type, ContentVoter::CREATE);
                return $contentManager->create($domain, $type, $this->normalizeData($domain, $type, $args['data'] ?? []) ?? [], $args['persist']);

            case 'update':
                $content = $this->contentOrException($domain, $contentManager, $type, ContentVoter::UPDATE);
                return $contentManager->update($domain, $type, $content, $this->normalizeData($domain, $type, $args['data'] ?? []), $args['persist']);

            case 'delete':
                $content = $this->contentOrException($domain, $contentManager, $type, ContentVoter::DELETE);
                return $contentManager->delete($domain, $type, $content, $args['persist']);

            default:
                return null;
        }
    }

    protected function normalizeData(Domain $domain, string $type, array $data) : array {

        $contentType = $domain->getContentTypeManager()->getAnyType($type);
        $normalizedData = [];

        foreach($data as $id => $fieldData) {
            $field = $contentType->getField($id);

            if($field->isListOf()) {
                $listData = [];
                foreach($fieldData ?? [] as $rowId => $rowData) {
                    $listData[$rowId] = $this->normalizeFieldData($field, $domain, $rowData);
                }
                $normalizedData[$id] = new FieldDataList($listData);
            }

            else {
                $normalizedData[$id] = $this->normalizeFieldData($field, $domain, $fieldData);
            }
        }

        return $normalizedData;
    }

    protected function normalizeFieldData(ContentTypeField $field, Domain $domain, $rowData) {
        if(!empty($field->getUnionTypes())) {
            $unionType = $domain->getContentTypeManager()->getUnionContentType($field->getReturnType());
            $selectedUnionType = array_keys($rowData)[0];
            $rowData = $rowData[$selectedUnionType];
            $field = $unionType->getField($selectedUnionType);
        }

        $fieldType = $this->fieldTypeManager->getFieldType($field->getType());
        return $fieldType->normalizeData($field, $rowData);
    }

    protected function contentOrException(Domain $domain, ContentManagerInterface $contentManager, string $type, string $attribute, $id = null) : ?ContentInterface {

        if($attribute === ContentVoter::CREATE) {
            $subject = $domain->getContentTypeManager()->getAnyType($type);

            if(empty($subject)) {
                throw new InvalidArgumentException(sprintf('Content type %s was not found.', $type));
            }

        } else {
            $subject = $contentManager->find($domain, $type, $id);

            if(empty($subject)) {
                return null;
            }
        }

        if(!$this->authorizationChecker->isGranted($attribute, $subject)) {
            throw new AccessDeniedException(sprintf('You are not allowed to %s content of type %s.', $attribute, $type));
        }

        return $subject instanceof ContentInterface ? $subject : null;
    }
}

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
use Symfony\Component\Validator\Validator\ValidatorInterface;
use UniteCMS\CoreBundle\Content\ContentInterface;
use UniteCMS\CoreBundle\Content\ContentManagerInterface;
use UniteCMS\CoreBundle\Content\FieldDataList;
use UniteCMS\CoreBundle\ContentType\ContentTypeField;
use UniteCMS\CoreBundle\Domain\Domain;
use UniteCMS\CoreBundle\Domain\DomainManager;
use UniteCMS\CoreBundle\Exception\ConstraintViolationsException;
use UniteCMS\CoreBundle\Field\FieldTypeManager;
use UniteCMS\CoreBundle\Security\Voter\ContentVoter;

class MutationResolver implements FieldResolverInterface
{

    /**
     * @var AuthorizationCheckerInterface $authorizationChecker
     */
    protected $authorizationChecker;

    /**
     * @var \Symfony\Component\Validator\Validator\ValidatorInterface $validator
     */
    protected $validator;

    /**
     * @var DomainManager $domainManager
     */
    protected $domainManager;

    /**
     * @var FieldTypeManager $fieldTypeManager
     */
    protected $fieldTypeManager;

    public function __construct(AuthorizationCheckerInterface $authorizationChecker, ValidatorInterface $validator, DomainManager $domainManager, FieldTypeManager $fieldTypeManager)
    {
        $this->authorizationChecker = $authorizationChecker;
        $this->validator = $validator;
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
        if(count($fieldNameParts) < 2) {
            return null;
        }

        $field = array_shift($fieldNameParts);
        $type = substr($info->fieldName, strlen($field));

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
                $content = $this->getOrCreate($contentManager, $domain, ContentVoter::CREATE, $type);
                $contentManager->update($domain, $content, $this->normalizeData($domain, $content, $args['data'] ?? []));
                $this->validate($content);

                if($args['persist']) {
                    $contentManager->persist($domain, $content, ContentManagerInterface::PERSIST_CREATE);
                }

                return $content;

            case 'update':
                $content = $this->getOrCreate($contentManager, $domain, ContentVoter::UPDATE, $type, $args['id']);
                $contentManager->update($domain, $content, $this->normalizeData($domain, $content, $args['data'] ?? []));
                $this->validate($content);

                if($args['persist']) {
                    $contentManager->persist($domain, $content, ContentManagerInterface::PERSIST_UPDATE);
                }

                return $content; 

            case 'delete':
                $content = $this->getOrCreate($contentManager, $domain, ContentVoter::DELETE, $type, $args['id']);
                $contentManager->delete($domain, $content);
                // TODO: $this->validate($content); implement if we add group support.

                if($args['persist']) {
                    $contentManager->persist($domain, $content, ContentManagerInterface::PERSIST_DELETE);
                }

                return $content;

            default:
                return null;
        }
    }

    protected function normalizeData(Domain $domain, ContentInterface $content, array $data) : array {

        $contentType = $domain->getContentTypeManager()->getAnyType($content->getType());
        $normalizedData = [];

        foreach($data as $id => $fieldData) {
            $field = $contentType->getField($id);

            if($field->isListOf()) {
                $listData = [];
                foreach($fieldData ?? [] as $rowId => $rowData) {
                    $listData[$rowId] = $this->normalizeFieldData($field, $domain, $content, $rowData);
                }
                $normalizedData[$id] = new FieldDataList($listData);
            }

            else {
                $normalizedData[$id] = $this->normalizeFieldData($field, $domain, $content, $fieldData);
            }
        }

        return $normalizedData;
    }

    protected function normalizeFieldData(ContentTypeField $field, Domain $domain, ContentInterface $content, $rowData) {
        if(!empty($field->getUnionTypes())) {
            $unionType = $domain->getContentTypeManager()->getUnionContentType($field->getReturnType());
            $selectedUnionType = array_keys($rowData)[0];
            $rowData = $rowData[$selectedUnionType];
            $field = $unionType->getField($selectedUnionType);
        }

        $fieldType = $this->fieldTypeManager->getFieldType($field->getType());
        return $fieldType->normalizeInputData($content, $field, $rowData);
    }

    /**
     * @param \UniteCMS\CoreBundle\Content\ContentManagerInterface $contentManager
     * @param \UniteCMS\CoreBundle\Domain\Domain $domain
     * @param string $attribute
     * @param string $type
     * @param string|null $id
     *
     * @return \UniteCMS\CoreBundle\Content\ContentInterface
     */
    protected function getOrCreate(ContentManagerInterface $contentManager, Domain $domain, string $attribute, string $type, string $id = null) : ContentInterface {
        $content = empty($id) ?
            $contentManager->create($domain, $type) :
            $contentManager->get($domain, $type, $id);

        if(empty($content)) {
            throw new InvalidArgumentException('Content was not found.');
        }

        if(!$this->authorizationChecker->isGranted($attribute, $content)) {
            throw new AccessDeniedException(sprintf('You are not allowed to %s content of type %s.', $attribute, $type));
        }

        return $content;
    }

    /**
     * @param \UniteCMS\CoreBundle\Content\ContentInterface $content
     * @return \UniteCMS\CoreBundle\Content\ContentInterface
     */
    protected function validate(ContentInterface $content) : ContentInterface {
        
        $violations = $this->validator->validate($content);

        if(count($violations) > 0) {
            throw new ConstraintViolationsException($violations);
        }
        
        return $content;
    }
}

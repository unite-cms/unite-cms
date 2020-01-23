<?php


namespace UniteCMS\CoreBundle\GraphQL\Resolver\Field;

use GraphQL\Error\Error;
use GraphQL\Error\UserError;
use GraphQL\Language\AST\ObjectTypeDefinitionNode;
use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\WrappingType;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use UniteCMS\CoreBundle\Content\ContentInterface;
use UniteCMS\CoreBundle\Content\ContentManagerInterface;
use UniteCMS\CoreBundle\Content\FieldDataMapper;
use UniteCMS\CoreBundle\Domain\Domain;
use UniteCMS\CoreBundle\Domain\DomainManager;
use UniteCMS\CoreBundle\Event\ContentEvent;
use UniteCMS\CoreBundle\Event\ContentEventAfter;
use UniteCMS\CoreBundle\Event\ContentEventBefore;
use UniteCMS\CoreBundle\Exception\ConstraintViolationsException;
use UniteCMS\CoreBundle\Exception\ContentAccessDeniedException;
use UniteCMS\CoreBundle\Exception\ContentNotFoundException;
use UniteCMS\CoreBundle\Query\ContentCriteria;
use UniteCMS\CoreBundle\Security\Voter\ContentVoter;

class MutationResolver implements FieldResolverInterface
{

    /**
     * @var AuthorizationCheckerInterface $authorizationChecker
     */
    protected $authorizationChecker;

    /**
     * @var EventDispatcherInterface $eventDispatcher
     */
    protected $eventDispatcher;

    /**
     * @var ValidatorInterface $validator
     */
    protected $validator;

    /**
     * @var DomainManager $domainManager
     */
    protected $domainManager;

    /**
     * @var FieldDataMapper $fieldDataMapper
     */
    protected $fieldDataMapper;

    public function __construct(AuthorizationCheckerInterface $authorizationChecker, EventDispatcherInterface $eventDispatcher, ValidatorInterface $validator, DomainManager $domainManager, FieldDataMapper $fieldDataMapper)
    {
        $this->authorizationChecker = $authorizationChecker;
        $this->eventDispatcher = $eventDispatcher;
        $this->validator = $validator;
        $this->domainManager = $domainManager;
        $this->fieldDataMapper = $fieldDataMapper;
    }

    /**
     * @inheritDoc
     */
    public function supports(string $typeName, ObjectTypeDefinitionNode $typeDefinitionNode): bool {
        return $typeName === 'Mutation';
    }

    /**
     * @inheritDoc
     * @throws ContentNotFoundException
     */
    public function resolve($value, $args, $context, ResolveInfo $info) {

        // Get current domain.
        $domain = $this->domainManager->current();

        // Get action and content type name parts from field name.
        $fieldNameParts = preg_split('/(?=[A-Z])/',$info->fieldName);
        if(count($fieldNameParts) < 2) {
            return null;
        }

        $field = array_shift($fieldNameParts);
        $type = substr($info->fieldName, strlen($field));

        // If not one of the defined fields
        if(!in_array($field, ['create', 'update', 'delete', 'revert', 'recover', 'permanent_delete'])) {
            return null;
        }

        // If no content manager could be found for this field, return null.
        if(!$contentManager = $this->contentManagerForField($domain, $info)) {
            return null;
        }

        // If this is a single content type, get id from repository.
        if($domain->getContentTypeManager()->getSingleContentType($type)) {
            $allSingleContent = $contentManager->find($domain, $type, new ContentCriteria());
            $args['id'] = $allSingleContent->getTotal() === 0 ? null : $allSingleContent->getResult()[0]->getId();
        }

        // Get or create content. Throws an exception, if something goes wrong.
        $content = $this->getContent($contentManager, $domain, $type, $args, $field);

        // Handle all fields
        switch ($field) {
            case 'create':
                $this->contentAccess(ContentVoter::CREATE, $content, $contentManager, $domain);
                return $contentManager->transactional($domain, function() use ($contentManager, $domain, $content, $args) {
                    $this->contentUpdate($contentManager, $domain, $content, $args['data'] ?? []);
                    return $this->contentPersist($contentManager, $domain, $content, ContentEvent::CREATE, $args['persist']);
                });

            case 'update':
                $this->contentAccess(ContentVoter::UPDATE, $content, $contentManager, $domain);

                return $contentManager->transactional($domain, function() use ($contentManager, $domain, $content, $args) {
                    $previousContent = $content->getData();
                    $this->contentUpdate($contentManager, $domain, $content, $args['data'] ?? []);
                    return $this->contentPersist($contentManager, $domain, $content, ContentEvent::UPDATE, $args['persist'], $previousContent);
                });

            case 'revert':
                $this->contentAccess(ContentVoter::UPDATE, $content, $contentManager, $domain);

                return $contentManager->transactional($domain, function() use ($contentManager, $domain, $content, $args) {
                    $previousContent = $content->getData();
                    $contentManager->revert($domain, $content, $args['version']);
                    return $this->contentPersist($contentManager, $domain, $content, ContentEvent::REVERT, $args['persist'], $previousContent);
                });

            case 'delete':
                $this->contentAccess(ContentVoter::DELETE, $content, $contentManager, $domain);

                return $contentManager->transactional($domain, function() use ($contentManager, $domain, $content, $args) {
                    $previousContent = $content->getData();
                    $contentManager->delete($domain, $content);
                    return $this->contentPersist($contentManager, $domain, $content, ContentEvent::DELETE, $args['persist'], $previousContent);
                });

            case 'recover':
                $this->contentAccess(ContentVoter::UPDATE, $content, $contentManager, $domain);

                $contentManager->transactional($domain, function() use ($contentManager, $domain, $content, $args) {
                    $previousContent = $content->getData();
                    $contentManager->recover($domain, $content);
                    $this->contentPersist($contentManager, $domain, $content, ContentEvent::RECOVER, $args['persist'], $previousContent);
                });

                // On recover: only return content if we really persist the change
                return $args['persist'] ? $content: null;

            case 'permanent_delete':
                $this->contentAccess(ContentVoter::PERMANENT_DELETE, $content, $contentManager, $domain);

                if(!$content->getDeleted() && empty($args['force'])) {
                    throw new UserError('You can only permanent delete content if it is already deleted or if you set the "force" argument to true.');
                }

                return $contentManager->transactional($domain, function() use ($contentManager, $domain, $content, $args) {
                    $previousContent = $content->getData();
                    $contentManager->permanentDelete($domain, $content);
                    return $this->contentPersist($contentManager, $domain, $content, ContentEvent::PERMANENT_DELETE, $args['persist'], $previousContent);
                });

            default:
                return null;
        }
    }

    /**
     * @param Domain $domain
     * @param ResolveInfo $info
     *
     * @return ContentManagerInterface|null
     */
    protected function contentManagerForField(Domain $domain, ResolveInfo $info) : ?ContentManagerInterface {

        // Actual return type of this field.
        $actualType = $info->returnType;

        if($actualType instanceof WrappingType) {
            $actualType = $actualType->getWrappedType(true);
        }

        if(!$actualType instanceof ObjectType) {
            return null;
        }

        /**
         * @var InterfaceType $contentInterface
         * @var InterfaceType $singleContentInterface
         * @var InterfaceType $userInterface
         */
        $contentInterface = null;
        $singleContentInterface = null;
        $userInterface = null;

        // Silently check which interfaces are available.
        try { $contentInterface = $info->schema->getType('UniteContent'); } catch (Error $e) {}
        try { $singleContentInterface = $info->schema->getType('UniteSingleContent'); } catch (Error $e) {}
        try { $userInterface = $info->schema->getType('UniteUser'); } catch (Error $e) {}

        if($contentInterface && $actualType->implementsInterface($contentInterface)) {
            return $domain->getContentManager();
        }

        else if($singleContentInterface && $actualType->implementsInterface($singleContentInterface)) {
            return $domain->getContentManager();
        }

        else if($userInterface && $actualType->implementsInterface($userInterface)) {
            return $domain->getUserManager();
        }

        return null;
    }

    /**
     * @param ContentManagerInterface $contentManager
     * @param Domain $domain
     * @param string $type
     * @param array $args
     * @param $field
     *
     * @return ContentInterface
     * @throws ContentNotFoundException
     */
    protected function getContent(ContentManagerInterface $contentManager, Domain $domain, string $type, array $args, $field) : ContentInterface {

        // Should we also include deleted content?
        $includeDeleted = in_array($field, ['recover', 'delete', 'permanent_delete']);


        // Get or create content.
        $content = empty($args['id']) ?
            $contentManager->create($domain, $type) :
            $contentManager->get($domain, $type, $args['id'], $includeDeleted);

        if(empty($content)) {
            throw new ContentNotFoundException(
                empty($args['id']) ?
                    'Content was not found.' :
                    sprintf('Content with id "%s" was not found.', $args['id'])
            );
        }

        return $content;
    }

    /**
     * @param string $attribute
     *
     * @param ContentInterface $content
     * @param ContentManagerInterface $contentManager
     * @param Domain $domain
     *
     * @return ContentInterface
     */
    protected function contentAccess(string $attribute, ContentInterface $content, ContentManagerInterface $contentManager, Domain $domain) : ContentInterface {

        if(!$this->authorizationChecker->isGranted($attribute, $content)) {
            $contentManager->noFlush($domain);
            throw new ContentAccessDeniedException(sprintf('You are not allowed to %s content of type "%s".', $attribute, $content->getType()));
        }

        return $content;
    }

    /**
     * @param ContentManagerInterface $contentManager
     * @param Domain $domain
     * @param ContentInterface $content
     * @param $data
     *
     * @return ContentInterface
     * @throws ContentNotFoundException
     */
    protected function contentUpdate(ContentManagerInterface $contentManager, Domain $domain, ContentInterface $content, $data) : ContentInterface {

        // Handle special _locale and _translate data
        if(array_key_exists('locale', $data) || array_key_exists('_translate', $data)) {
            if ($domain->getContentTypeManager()->getAnyType($content->getType())->isTranslatable()) {

                if(array_key_exists('locale', $data)) {
                    $content->setLocale($data['locale']);
                    unset($data['locale']);
                }

                if(array_key_exists('_translate', $data)) {

                    $translate = null;

                    if($data['_translate']) {
                        $translate = $contentManager->get($domain, $content->getType(), $data['_translate']);

                        if (!$translate) {
                            throw new ContentNotFoundException();
                        }
                    }

                    $content->setTranslate($translate);
                    unset($data['_translate']);
                }
            }
        }

        $contentManager->update($domain, $content, $this->fieldDataMapper->mapToFieldData($domain, $content, $data));

        return $content;
    }

    /**
     * @param ContentManagerInterface $contentManager
     * @param Domain $domain
     * @param ContentInterface $content
     * @param string $eventName
     * @param bool $persist
     *
     * @param array $previousContent
     * @return ContentInterface
     */
    protected function contentPersist(ContentManagerInterface $contentManager, Domain $domain, ContentInterface $content, string $eventName, bool $persist = false, array $previousContent = []) : ContentInterface {

        // Validate content for given event group.
        $violations = $this->validator->validate($content, null, [Constraint::DEFAULT_GROUP, $eventName]);

        // Throw exception, if there where constraint violations.
        if(count($violations) > 0) {
            $contentManager->noFlush($domain);
            throw new ConstraintViolationsException($violations);
        }

        // Persist content.
        if($persist) {
            $this->eventDispatcher->dispatch(new ContentEventBefore($content, $previousContent), $eventName);
            $contentManager->flush($domain);
            $this->eventDispatcher->dispatch(new ContentEventAfter($content, $previousContent), 'AFTER ' . $eventName);
        } else {
            $contentManager->noFlush($domain);
        }

        return $content;
    }
}

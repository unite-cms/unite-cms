<?php

namespace UniteCMS\CoreBundle\SchemaType\Types;

use UniteCMS\CoreBundle\Entity\ContentType;
use UniteCMS\CoreBundle\Entity\DomainMemberType;
use UniteCMS\CoreBundle\Entity\DomainMemberTypeField;
use UniteCMS\CoreBundle\Entity\FieldableContent;
use UniteCMS\CoreBundle\Entity\Setting;
use UniteCMS\CoreBundle\Entity\SettingType;
use UniteCMS\CoreBundle\Entity\SettingTypeField;
use UniteCMS\CoreBundle\Entity\SoftDeleteableFieldableContent;
use UniteCMS\CoreBundle\Exception\NotValidException;
use UniteCMS\CoreBundle\Model\FieldableFieldContent;
use Doctrine\ORM\EntityManager;
use GraphQL\Error\UserError;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Symfony\Component\Form\Extension\Validator\ViolationMapper\ViolationMapper;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use UniteCMS\CoreBundle\Entity\Content;
use UniteCMS\CoreBundle\Entity\ContentTypeField;
use UniteCMS\CoreBundle\Exception\UserErrorAtPath;
use UniteCMS\CoreBundle\Field\FieldTypeManager;
use UniteCMS\CoreBundle\Form\ContentDeleteFormType;
use UniteCMS\CoreBundle\Form\FieldableFormBuilder;
use UniteCMS\CoreBundle\SchemaType\IdentifierNormalizer;
use UniteCMS\CoreBundle\Security\Voter\ContentVoter;
use UniteCMS\CoreBundle\Security\Voter\DomainMemberVoter;
use UniteCMS\CoreBundle\Security\Voter\FieldableFieldVoter;
use UniteCMS\CoreBundle\Security\Voter\SettingVoter;
use UniteCMS\CoreBundle\Service\FieldableContentManager;
use UniteCMS\CoreBundle\Service\UniteCMSManager;
use UniteCMS\CoreBundle\SchemaType\SchemaTypeManager;

class MutationType extends AbstractType
{


    /**
     * @var SchemaTypeManager $schemaTypeManager
     */
    private $schemaTypeManager;

    /**
     * @var EntityManager $entityManager
     */
    private $entityManager;

    /**
     * @var UniteCMSManager $uniteCMSManager
     */
    private $uniteCMSManager;

    /**
     * @var AuthorizationChecker $authorizationChecker
     */
    private $authorizationChecker;

    /**
     * @var ValidatorInterface $validator
     */
    private $validator;

    /**
     * @var FieldableFormBuilder $fieldableFormBuilder
     */
    private $fieldableFormBuilder;

    /**
     * @var FormFactoryInterface $formFactory
     */
    private $formFactory;

    /**
     * @var FieldTypeManager $fieldTypeManager
     */
    private $fieldTypeManager;

    /**
     * @var FieldableContentManager $contentManager
     */
    private $contentManager;

    public function __construct(
        SchemaTypeManager $schemaTypeManager,
        EntityManager $entityManager,
        UniteCMSManager $uniteCMSManager,
        AuthorizationChecker $authorizationChecker,
        ValidatorInterface $validator,
        FieldableFormBuilder $fieldableFormBuilder,
        FormFactoryInterface $formFactory,
        FieldTypeManager $fieldTypeManager,
        FieldableContentManager $contentManager
    ) {
        $this->schemaTypeManager = $schemaTypeManager;
        $this->entityManager = $entityManager;
        $this->uniteCMSManager = $uniteCMSManager;
        $this->authorizationChecker = $authorizationChecker;
        $this->validator = $validator;
        $this->fieldableFormBuilder = $fieldableFormBuilder;
        $this->formFactory = $formFactory;
        $this->fieldTypeManager = $fieldTypeManager;
        $this->contentManager = $contentManager;
        parent::__construct();
    }

    /**
     * Define all fields of this type.
     *
     * @return array
     */
    protected function fields()
    {
        $fields = [];

        // Append Content types.
        foreach ($this->uniteCMSManager->getDomain()->getContentTypes() as $contentType) {

            // If the current user is not allowed to access this content type, skip adding a get and find action.
            if(!$this->authorizationChecker->isGranted(ContentVoter::LIST, $contentType)) {
                continue;
            }

            $key = IdentifierNormalizer::graphQLType($contentType, '');

            $fields['create' . $key] = [
                'type' => $this->schemaTypeManager->getSchemaType($key . 'Content', $this->uniteCMSManager->getDomain()),
                'args' => [
                    'persist' => [
                        'type' => Type::nonNull(Type::boolean()),
                        'description' => 'Only if is set to true, the content will be created. Data will be validated anyway.',
                    ],
                ],
            ];

            $fields['update' . $key] = [
                'type' => $this->schemaTypeManager->getSchemaType($key . 'Content', $this->uniteCMSManager->getDomain()),
                'args' => [
                    'id' => [
                        'type' => Type::nonNull(Type::id()),
                        'description' => 'The id of the content item to get.',
                    ],
                    'persist' => [
                        'type' => Type::nonNull(Type::boolean()),
                        'description' => 'Only if is set to true, the content will be updated. Data will be validated anyway.',
                    ],
                ],
            ];

            $fields['revert' . $key] = [
                'type' => $this->schemaTypeManager->getSchemaType($key . 'Content', $this->uniteCMSManager->getDomain()),
                'args' => [
                    'id' => [
                        'type' => Type::nonNull(Type::id()),
                        'description' => 'The id of the content item to get.',
                    ],
                    'version' => [
                        'type' => Type::nonNull(Type::int()),
                        'description' => 'The version number to revert the content to.',
                    ],
                    'persist' => [
                        'type' => Type::nonNull(Type::boolean()),
                        'description' => 'Only if is set to true, the content will be reverted. Data will be validated anyway.',
                    ],
                ],
            ];

            $fields['delete' . $key] = [
                'type' => $this->schemaTypeManager->getSchemaType('DeletedContentResult', $this->uniteCMSManager->getDomain()),
                'args' => [
                    'id' => [
                        'type' => Type::nonNull(Type::id()),
                        'description' => 'The id of the content item to delete.',
                    ],
                    'persist' => [
                        'type' => Type::nonNull(Type::boolean()),
                        'description' => 'Only if is set to true, the content will be delete. Data will be validated anyway.',
                    ],
                    'definitely' => [
                        'type' => Type::boolean(),
                        'description' => 'If set to true, you can definitely delete content that was deleted before. This action cannot be undone.',
                        'defaultValue' => false,
                    ],
                ],
            ];

            $fields['recover' . $key] = [
                'type' => $this->schemaTypeManager->getSchemaType($key . 'Content', $this->uniteCMSManager->getDomain()),
                'args' => [
                    'id' => [
                        'type' => Type::nonNull(Type::id()),
                        'description' => 'The id of the deleted content item to recover.',
                    ],
                    'persist' => [
                        'type' => Type::nonNull(Type::boolean()),
                        'description' => 'Only if is set to true, the content will be recovered. Data will be validated anyway.',
                    ],
                ],
            ];

            // If this content type has defined fields, we can create and update content with data.
            $fullContentType = $this->entityManager->getRepository('UniteCMSCoreBundle:ContentType')->find($contentType->getId());
            $fieldsWithInput = $fullContentType->getFields()->filter(function(ContentTypeField $field){
                return $this->fieldTypeManager->getFieldType($field->getType())->getGraphQLInputType($field, $this->schemaTypeManager) !== null;
            });

            if($fieldsWithInput->count() > 0) {
                $fields['create' . $key]['args']['data'] = [
                    'type' => Type::nonNull($this->schemaTypeManager->getSchemaType($key . 'ContentInput', $this->uniteCMSManager->getDomain())),
                    'description' => 'The content data to save.',
                ];
                $fields['update' . $key]['args']['data'] = [
                    'type' => Type::nonNull($this->schemaTypeManager->getSchemaType($key . 'ContentInput', $this->uniteCMSManager->getDomain())),
                    'description' => 'The content data to save.',
                ];
            }

            // If this content type has defined locales, we can add set them via the locale argument.
            if(!empty($fullContentType->getLocales())) {
                $fields['create' . $key]['args']['locale'] = [
                    'type' => Type::nonNull(Type::string()),
                    'description' => 'Content will be created in this locale.',
                ];
                $fields['update' . $key]['args']['locale'] = [
                    'type' => Type::string(),
                    'description' => 'Update locale of this content.',
                ];
            }
        }

        // Append Setting types.
        foreach ($this->uniteCMSManager->getDomain()->getSettingTypes() as $settingType) {

            // If the current user is not allowed to access this setting type, skip adding.
            if(!$this->authorizationChecker->isGranted(SettingVoter::UPDATE, $settingType)) {
                continue;
            }

            $key = IdentifierNormalizer::graphQLType($settingType);

            $fields['update' . $key] = [
                'type' => $this->schemaTypeManager->getSchemaType($key, $this->uniteCMSManager->getDomain()),
                'args' => [
                    'persist' => [
                        'type' => Type::nonNull(Type::boolean()),
                        'description' => 'Only if is set to true, the setting will be updated. Data will be validated anyway.',
                    ],
                    'locale' => [
                        'type' => Type::string(),
                        'description' => 'If this setting type have multiple locales and you want to update not the default one, set it.',
                    ],
                ],
            ];

            $fields['revert' . $key] = [
                'type' => $this->schemaTypeManager->getSchemaType($key, $this->uniteCMSManager->getDomain()),
                'args' => [
                    'version' => [
                        'type' => Type::nonNull(Type::int()),
                        'description' => 'The version number to revert the setting to.',
                    ],
                    'persist' => [
                        'type' => Type::nonNull(Type::boolean()),
                        'description' => 'Only if is set to true, the setting will be reverted. Data will be validated anyway.',
                    ],
                    'locale' => [
                        'type' => Type::string(),
                        'description' => 'If this setting type have multiple locales and you want to update not the default one, set it.',
                    ],
                ],
            ];

            // If this setting type has defined fields, we can create and update content with data.
            $fullSettingType = $this->entityManager->getRepository('UniteCMSCoreBundle:SettingType')->find($settingType->getId());
            $fieldsWithInput = $fullSettingType->getFields()->filter(function(SettingTypeField$field){
                return $this->fieldTypeManager->getFieldType($field->getType())->getGraphQLInputType($field, $this->schemaTypeManager) !== null;
            });

            if($fieldsWithInput->count() > 0) {
                $fields['update' . $key]['args']['data'] = [
                    'type' => Type::nonNull($this->schemaTypeManager->getSchemaType($key . 'Input', $this->uniteCMSManager->getDomain())),
                    'description' => 'The setting data to save.',
                ];
            }
        }

        // Append Domain Member types.
        foreach ($this->uniteCMSManager->getDomain()->getDomainMemberTypes() as $domainMemberType) {

            // If the current user is not allowed to access this domain member type, skip adding.
            if(!$this->authorizationChecker->isGranted(DomainMemberVoter::LIST, $domainMemberType)) {
                continue;
            }

            $key = IdentifierNormalizer::graphQLType($domainMemberType);

            $fields['update' . $key] = [
                'type' => $this->schemaTypeManager->getSchemaType($key, $this->uniteCMSManager->getDomain()),
                'args' => [
                    'id' => [
                        'type' => Type::nonNull(Type::id()),
                        'description' => 'The id of the member item to get.',
                    ],
                    'persist' => [
                        'type' => Type::nonNull(Type::boolean()),
                        'description' => 'Only if is set to true, the member will be updated. Data will be validated anyway.',
                    ],
                ],
            ];

            $fields['revert' . $key] = [
                'type' => $this->schemaTypeManager->getSchemaType($key, $this->uniteCMSManager->getDomain()),
                'args' => [
                    'id' => [
                        'type' => Type::nonNull(Type::id()),
                        'description' => 'The id of the member item to get.',
                    ],
                    'version' => [
                        'type' => Type::nonNull(Type::int()),
                        'description' => 'The version number to revert the member to.',
                    ],
                    'persist' => [
                        'type' => Type::nonNull(Type::boolean()),
                        'description' => 'Only if is set to true, the member will be reverted. Data will be validated anyway.',
                    ],
                ],
            ];

            $fields['delete' . $key] = [
                'type' => $this->schemaTypeManager->getSchemaType('DeletedContentResult', $this->uniteCMSManager->getDomain()),
                'args' => [
                    'id' => [
                        'type' => Type::nonNull(Type::id()),
                        'description' => 'The id of the member item to delete.',
                    ],
                    'persist' => [
                        'type' => Type::nonNull(Type::boolean()),
                        'description' => 'Only if is set to true, the member will be delete. Data will be validated anyway.',
                    ],
                ],
            ];

            // If this content type has defined fields, we can create and update content with data.
            $fullDomainMemberType = $this->entityManager->getRepository('UniteCMSCoreBundle:DomainMemberType')->find($domainMemberType->getId());
            $fieldsWithInput = $fullDomainMemberType->getFields()->filter(function(DomainMemberTypeField $field){
                return $this->fieldTypeManager->getFieldType($field->getType())->getGraphQLInputType($field, $this->schemaTypeManager) !== null;
            });

            if($fieldsWithInput->count() > 0) {
                $fields['update' . $key]['args']['data'] = [
                    'type' => Type::nonNull($this->schemaTypeManager->getSchemaType($key . 'Input', $this->uniteCMSManager->getDomain())),
                    'description' => 'The member data to save.',
                ];
            }
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
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    protected function resolveField($value, array $args, $context, ResolveInfo $info)
    {
        $args['data'] = $args['data'] ?? [];

        $fieldableType = ContentType::class;
        $parts = IdentifierNormalizer::graphQLSchemaSplitter($info->fieldName);
        if(count($parts) == 3 && $parts[2] === 'Setting') {
            $fieldableType = SettingType::class;
        }
        else if(count($parts) == 3 && $parts[2] === 'Member') {
            $fieldableType = DomainMemberType::class;
        }

        // Resolve create content type
        if(substr($info->fieldName, 0, 6) == 'create') {
            return $this->resolveCreateContent(
                IdentifierNormalizer::fromGraphQLFieldName($info->fieldName),
                $fieldableType,
                $value, $args, $context, $info
            );
        }

        // Resolve update content type
        elseif(substr($info->fieldName, 0, 6) == 'update') {
            return $this->resolveUpdateContent(
                IdentifierNormalizer::fromGraphQLFieldName($info->fieldName),
                $fieldableType,
                $value, $args, $context, $info
            );
        }

        // Resolve revert content type
        elseif(substr($info->fieldName, 0, 6) == 'revert') {
            return $this->resolveRevertContent(
                IdentifierNormalizer::fromGraphQLFieldName($info->fieldName),
                $fieldableType,
                $value, $args, $context, $info
            );
        }

        // Resolve delete content type
        elseif(substr($info->fieldName, 0, 6) == 'delete') {
            return $this->resolveDeleteContent(
                IdentifierNormalizer::fromGraphQLFieldName($info->fieldName),
                $fieldableType,
                $value, $args, $context, $info
            );
        }

        // Resolve delete content type
        elseif(substr($info->fieldName, 0, 7) == 'recover') {
            return $this->resolveRecoverContent(
                IdentifierNormalizer::fromGraphQLFieldName($info->fieldName),
                $fieldableType,
                $value, $args, $context, $info
            );
        }

        return null;
    }

    /**
     * Finds a fieldable content element by graphql request information.
     *
     * @param string $fieldableType
     * @param array $args
     * @param $context
     * @param ResolveInfo $info
     * @return FieldableContent|null
     */
    protected function findContent(string $fieldableType, array $args, $context, ResolveInfo $info) : ?FieldableContent {
        switch ($fieldableType) {
            case ContentType::class:
                $id = $args['id'];
                return $this->entityManager->getRepository('UniteCMSCoreBundle:Content')->find($id);
            case SettingType::class:
                $parts = IdentifierNormalizer::graphQLSchemaSplitter($info->fieldName);
                $settingType = $this->entityManager->getRepository('UniteCMSCoreBundle:SettingType')->findOneBy(['domain' => $this->uniteCMSManager->getDomain(), 'identifier' => strtolower($parts[1])]);
                return $settingType ? $settingType->getSetting($args['locale'] ?? null) : null;
            case DomainMemberType::class:
                $id = $args['id'];
                return $this->entityManager->getRepository('UniteCMSCoreBundle:DomainMember')->find($id);
            default:
                return null;
        }
    }

    /**
     * Resolve create content.
     *
     * @param $identifier
     * @param string $fieldableType
     * @param $value
     * @param $args
     * @param $context
     * @param \GraphQL\Type\Definition\ResolveInfo $info
     *
     * @return mixed
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function resolveCreateContent($identifier, string $fieldableType, $value, $args, $context, ResolveInfo $info) {

        if (!$contentType = $this->entityManager->getRepository('UniteCMSCoreBundle:ContentType')->findOneBy(
            [
                'domain' => $this->uniteCMSManager->getDomain(),
                'identifier' => $identifier,
            ]
        )) {
            throw new UserError("ContentType '$identifier' was not found in domain.");
        }

        if (!$this->authorizationChecker->isGranted(ContentVoter::CREATE, $contentType)) {
            throw new UserError("You are not allowed to create content in content type '$contentType'.");
        }

        $content = new Content();
        $form = $this->fieldableFormBuilder->createForm($contentType, $content);

        // If mutations are performed via the main firewall instead of the api firewall, a csrf token must be passed to the form.
        if(is_array($args['data']) && !empty($context['csrf_token'])) {
            $args['data']['_token'] = $context['csrf_token'];
        }

        // Set locale from arg to form data.
        if(!empty($contentType->getLocales()) && !empty($args['locale'])) {
            $args['data']['locale'] = $args['locale'];
        }

        // Remove field values from field, where the user has no access.
        foreach($contentType->getFields() as $field) {
            if(array_key_exists($field->getIdentifier(), $args['data']) && !$this->authorizationChecker->isGranted(FieldableFieldVoter::UPDATE, new FieldableFieldContent($field, $content))) {
                unset($args['data'][$field->getIdentifier()]);
            }
        }

        $form->submit($args['data']);

        if ($form->isSubmitted() && $form->isValid()) {

            // Assign data to content object.
            $content->setContentType($contentType);
            $this->fieldableFormBuilder->assignDataToFieldableContent($content, $form->getData());

            // If content errors were found, map them to the form.
            $violations = $this->validator->validate($content);

            if (count($violations) > 0) {
                $violationMapper = new ViolationMapper();
                foreach ($violations as $violation) {
                    $violationMapper->mapViolation($violation, $form);
                }

            // If content is valid.
            } else {

                if($args['persist']) {
                    $this->entityManager->persist($content);
                    $this->entityManager->flush();
                }

                return $content;
            }
        }

        foreach($form->getErrors(true, true) as $error) {
            throw UserErrorAtPath::createFromFormError($error);
        }

        return null;
    }

    /**
     * Resolve update content.
     *
     * @param $identifier
     * @param string $fieldableType
     * @param $value
     * @param $args
     * @param $context
     * @param \GraphQL\Type\Definition\ResolveInfo $info
     *
     * @return mixed
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function resolveUpdateContent($identifier, string $fieldableType,  $value, $args, $context, ResolveInfo $info) {

        if(!$content = $this->findContent($fieldableType, $args, $context, $info)) {
            throw new UserError("Not found.");
        }

        if (!$this->contentManager->isGranted($content, FieldableContentManager::PERMISSION_UPDATE)) {
            throw new UserError("You are not allowed to update this item.");
        }

        $form = $this->fieldableFormBuilder->createForm($content->getEntity(), $content);

        // If mutations are performed via the main firewall instead of the api firewall, a csrf token must be passed to the form.
        if(is_array($args['data']) && !empty($context['csrf_token'])) {
            $args['data']['_token'] = $context['csrf_token'];
        }

        // Set default locale if non was set by the api.
        if (!empty($content->getEntity()->getLocales()) && $content instanceof Content) {
            $args['data']['locale'] = $args['locale'] ?? $content->getLocale();
        }

        // Remove field values from field, where the user has no access.
        foreach($content->getEntity()->getFields() as $field) {
            if(array_key_exists($field->getIdentifier(), $args['data']) && !$this->authorizationChecker->isGranted(FieldableFieldVoter::UPDATE, new FieldableFieldContent($field, $content))) {
                unset($args['data'][$field->getIdentifier()]);
            }
        }

        $form->submit($args['data'], false);

        if ($form->isSubmitted() && $form->isValid()) {

            // Assign data to content object.
            $this->fieldableFormBuilder->assignDataToFieldableContent($content, $form->getData());

            // If content errors were found, map them to the form.
            $violations = $this->validator->validate($content);
            if (count($violations) > 0) {
                $violationMapper = new ViolationMapper();
                foreach ($violations as $violation) {
                    $violationMapper->mapViolation($violation, $form);
                }

            // If content is valid.
            } else {

                if($args['persist']) {
                    $this->entityManager->flush();
                }

                // If this is a setting object, we must always return default lang.
                if($content instanceof Setting) {
                    return $content->getSettingType()->getSetting();
                } else {
                    return $content;
                }
            }
        }

        foreach($form->getErrors(true, true) as $error) {
            throw UserErrorAtPath::createFromFormError($error);
        }

        return null;
    }

    /**
     * Resolve revert content.
     *
     * @param $identifier
     * @param string $fieldableType
     * @param $value
     * @param $args
     * @param $context
     * @param \GraphQL\Type\Definition\ResolveInfo $info
     *
     * @return mixed
     */
    private function resolveRevertContent($identifier, string $fieldableType, $value, $args, $context, ResolveInfo $info) {

        if(!$content = $this->findContent($fieldableType, $args, $context, $info)) {
            throw new UserError("Content was not found.");
        }

        if (!$this->contentManager->isGranted($content, FieldableContentManager::PERMISSION_UPDATE)) {
            throw new UserError("You are not allowed to revert this item.");
        }

        $form = $this->formFactory->create(ContentDeleteFormType::class);

        // If mutations are performed via the main firewall instead of the api firewall, a csrf token must be passed to the form.
        $data = [];
        if(!empty($context['csrf_token'])) {
            $data['_token'] = $context['csrf_token'];
        }

        $form->submit($data);

        if($form->isSubmitted() && $form->isValid()) {
            $content = $this->contentManager->revert($content, $args['version'], $args['persist']);

            // If this is a setting object, we must always return default lang.
            if($content instanceof Setting) {
                return $content->getSettingType()->getSetting();
            } else {
                return $content;
            }
        }

        return null;
    }

    /**
     * Resolve delete content.
     *
     * @param $identifier
     * @param string $fieldableType
     * @param $value
     * @param $args
     * @param $context
     * @param \GraphQL\Type\Definition\ResolveInfo $info
     *
     * @return mixed
     */
    private function resolveDeleteContent($identifier, string $fieldableType, $value, $args, $context, ResolveInfo $info) {

        $fieldable = $this->contentManager->findFieldable($this->uniteCMSManager->getDomain(), $identifier, $fieldableType);
        $content = $this->contentManager->find($fieldable, $args['id'], $args['definitely'] ?? false);

        if(!$content || (!empty($args['definitely']) && (!$content instanceof SoftDeleteableFieldableContent || !$content->getDeleted()))) {
            throw new UserError("Content was not found.");
        }

        if (!$this->contentManager->isGranted($content, FieldableContentManager::PERMISSION_DELETE)) {
            throw new UserError(sprintf("You are not allowed to delete content with id '%s'.", $args['id']));
        }

        $form = $this->formFactory->create(ContentDeleteFormType::class);

        // If mutations are performed via the main firewall instead of the api firewall, a csrf token must be passed to the form.
        $data = [];
        if(!empty($context['csrf_token'])) {
            $data['_token'] = $context['csrf_token'];
        }

        $form->submit($data);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                if(!empty($args['definitely'])) {
                    $content = $this->contentManager->deleteDefinitely($content, $args['persist']);
                } else {
                    $content = $this->contentManager->delete($content, $args['persist']);
                }
                return [
                    'id' => $args['id'],
                    'deleted' => $content instanceof SoftDeleteableFieldableContent ? (!empty($content->getId()) && !empty($content->getDeleted())) : false,
                    'definitely_deleted' => empty($content->getId()),
                ];

            } catch (NotValidException $exception) {
                $exception->mapToForm($form);
            }
        }

        foreach($form->getErrors(true, true) as $error) {
            throw UserErrorAtPath::createFromFormError($error);
        }

        return null;
    }

    /**
     * Resolve recover content.
     *
     * @param $identifier
     * @param string $fieldableType
     * @param $value
     * @param $args
     * @param $context
     * @param \GraphQL\Type\Definition\ResolveInfo $info
     *
     * @return mixed
     */
    private function resolveRecoverContent($identifier, string $fieldableType, $value, $args, $context, ResolveInfo $info) {

        $fieldable = $this->contentManager->findFieldable($this->uniteCMSManager->getDomain(), $identifier, $fieldableType);
        $content = $this->contentManager->find($fieldable, $args['id'], true);

        if(!$content || !$content instanceof SoftDeleteableFieldableContent || $content->getDeleted() == null) {
            throw new UserError("Content was not found.");
        }

        if (!$this->contentManager->isGranted($content, FieldableContentManager::PERMISSION_UPDATE)) {
            throw new UserError("You are not allowed to recover this content.");
        }

        $form = $this->formFactory->create(ContentDeleteFormType::class);

        // If mutations are performed via the main firewall instead of the api firewall, a csrf token must be passed to the form.
        $data = [];
        if(!empty($context['csrf_token'])) {
            $data['_token'] = $context['csrf_token'];
        }

        $form->submit($data);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                return $this->contentManager->recover($content, $args['persist']);
            } catch (NotValidException $exception) {
                $exception->mapToForm($form);
            }
        }

        foreach($form->getErrors(true, true) as $error) {
            throw UserErrorAtPath::createFromFormError($error);
        }

        return null;
    }
}

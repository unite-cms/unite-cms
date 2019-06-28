<?php

namespace UniteCMS\CoreBundle\SchemaType\Types;

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
use UniteCMS\CoreBundle\Security\Voter\FieldableFieldVoter;
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

        // Resolve create content type
        if(substr($info->fieldName, 0, 6) == 'create') {
            return $this->resolveCreateContent(
                IdentifierNormalizer::fromGraphQLFieldName($info->fieldName),
                $value, $args, $context, $info
            );
        }

        // Resolve update content type
        elseif(substr($info->fieldName, 0, 6) == 'update') {
            return $this->resolveUpdateContent(
                IdentifierNormalizer::fromGraphQLFieldName($info->fieldName),
                $value, $args, $context, $info
            );
        }

        // Resolve delete content type
        elseif(substr($info->fieldName, 0, 6) == 'delete') {
            return $this->resolveDeleteContent(
                IdentifierNormalizer::fromGraphQLFieldName($info->fieldName),
                $value, $args, $context, $info
            );
        }

        return null;
    }


    /**
     * Resolve create content.
     *
     * @param $identifier
     * @param $value
     * @param $args
     * @param $context
     * @param \GraphQL\Type\Definition\ResolveInfo $info
     *
     * @return mixed
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function resolveCreateContent($identifier, $value, $args, $context, ResolveInfo $info) {

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
     * @param $value
     * @param $args
     * @param $context
     * @param \GraphQL\Type\Definition\ResolveInfo $info
     *
     * @return mixed
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function resolveUpdateContent($identifier, $value, $args, $context, ResolveInfo $info) {

        $id = $args['id'];
        $content = $this->entityManager->getRepository('UniteCMSCoreBundle:Content')->find($id);

        if(!$content) {
            throw new UserError("Content was not found.");
        }

        if (!$this->authorizationChecker->isGranted(ContentVoter::UPDATE, $content)) {
            throw new UserError("You are not allowed to update content with id '$id'.");
        }

        $form = $this->fieldableFormBuilder->createForm($content->getContentType(), $content);

        // If mutations are performed via the main firewall instead of the api firewall, a csrf token must be passed to the form.
        if(is_array($args['data']) && !empty($context['csrf_token'])) {
            $args['data']['_token'] = $context['csrf_token'];
        }

        // Set default locale if non was set by the api.
        if (!empty($content->getContentType()->getLocales())) {
            $args['data']['locale'] = $args['locale'] ?? $content->getLocale();
        }

        // Remove field values from field, where the user has no access.
        foreach($content->getContentType()->getFields() as $field) {
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

                return $content;
            }
        }

        foreach($form->getErrors(true, true) as $error) {
            throw UserErrorAtPath::createFromFormError($error);
        }

        return null;
    }

    /**
     * Resolve delete content.
     *
     * @param $identifier
     * @param $value
     * @param $args
     * @param $context
     * @param \GraphQL\Type\Definition\ResolveInfo $info
     *
     * @return mixed
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function resolveDeleteContent($identifier, $value, $args, $context, ResolveInfo $info) {

        $id = $args['id'];
        $content = $this->entityManager->getRepository('UniteCMSCoreBundle:Content')->find($id);

        if(!$content) {
            throw new UserError("Content was not found.");
        }

        if (!$this->authorizationChecker->isGranted(ContentVoter::DELETE, $content)) {
            throw new UserError("You are not allowed to delete content with id '$id'.");
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
                $this->contentManager->delete($content, $args['persist']);
            } catch (NotValidException $exception) {
                $exception->mapToForm($form);
            }

            if($args['persist']) {
                return [
                    'id' => $args['id'],
                    'deleted' => !!$args['persist'],
                ];
            }
        }

        foreach($form->getErrors(true, true) as $error) {
            throw UserErrorAtPath::createFromFormError($error);
        }

        return null;
    }
}

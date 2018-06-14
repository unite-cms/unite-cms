<?php

namespace UniteCMS\CoreBundle\SchemaType\Types;

use Doctrine\ORM\EntityManager;
use GraphQL\Error\UserError;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Symfony\Component\Form\Extension\Validator\ViolationMapper\ViolationMapper;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use UniteCMS\CoreBundle\Entity\Content;
use UniteCMS\CoreBundle\Field\FieldTypeManager;
use UniteCMS\CoreBundle\Form\FieldableFormBuilder;
use UniteCMS\CoreBundle\SchemaType\IdentifierNormalizer;
use UniteCMS\CoreBundle\Security\Voter\ContentVoter;
use UniteCMS\CoreBundle\Service\UniteCMSManager;
use UniteCMS\CoreBundle\SchemaType\SchemaTypeManager;

class MutationType extends AbstractType
{


    /**
     * @var SchemaTypeManager $schemaTypeManager
     */
    private $schemaTypeManager;

    /**
     * @var FieldTypeManager $fieldTypeManager
     */
    private $fieldTypeManager;

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

    public function __construct(
        SchemaTypeManager $schemaTypeManager,
        FieldTypeManager $fieldTypeManager,
        EntityManager $entityManager,
        UniteCMSManager $uniteCMSManager,
        AuthorizationChecker $authorizationChecker,
        ValidatorInterface $validator,
        FieldableFormBuilder $fieldableFormBuilder
    ) {
        $this->schemaTypeManager = $schemaTypeManager;
        $this->fieldTypeManager = $fieldTypeManager;
        $this->entityManager = $entityManager;
        $this->uniteCMSManager = $uniteCMSManager;
        $this->authorizationChecker = $authorizationChecker;
        $this->validator = $validator;
        $this->fieldableFormBuilder = $fieldableFormBuilder;
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
            $key = IdentifierNormalizer::graphQLType($contentType, '');

            $fields['create' . $key] = [
                'type' => $this->schemaTypeManager->getSchemaType($key . 'Content', $this->uniteCMSManager->getDomain()),
            ];

            $fields['update' . $key] = [
                'type' => $this->schemaTypeManager->getSchemaType($key . 'Content', $this->uniteCMSManager->getDomain()),
                'args' => [
                    'id' => [
                        'type' => Type::nonNull(Type::id()),
                        'description' => 'The id of the content item to get.',
                    ],
                ],
            ];

            // If this content type has defined fields, we can create and update content with data.
            $fullContentType = $this->entityManager->getRepository('UniteCMSCoreBundle:ContentType')->find($contentType->getId());
            if($fullContentType->getFields()->count() > 0) {
                $fields['create' . $key]['args']['data'] = [
                    'type' => Type::nonNull($this->schemaTypeManager->getSchemaType($key . 'ContentInput', $this->uniteCMSManager->getDomain())),
                    'description' => 'The content data to save.',
                ];
                $fields['update' . $key]['args']['data'] = [
                    'type' => Type::nonNull($this->schemaTypeManager->getSchemaType($key . 'ContentInput', $this->uniteCMSManager->getDomain())),
                    'description' => 'The content data to save.',
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

        // normalize data.
        if(is_array($args['data'])) {
            $args['data'] = IdentifierNormalizer::fromGraphQLData($args['data'], $this->fieldTypeManager, $contentType);
        }

        // If mutations are performed via the main firewall instead of the api firewall, a csrf token must be passed to the form.
        if(is_array($args['data']) && !empty($context['csrf_token'])) {
            $args['data']['_token'] = $context['csrf_token'];
        }

        $form->submit($args['data']);

        if ($form->isSubmitted() && $form->isValid()) {

            $data = $form->getData();

            if (isset($data['locale'])) {
                $content->setLocale($data['locale']);
                unset($data['locale']);
            }

            $content
                ->setContentType($contentType)
                ->setData($data);

            // If content errors were found, map them to the form.
            $violations = $this->validator->validate($content);

            if (count($violations) > 0) {
                $violationMapper = new ViolationMapper();
                foreach ($violations as $violation) {
                    $violationMapper->mapViolation($violation, $form);
                }

            // If content is valid.
            } else {
                $this->entityManager->persist($content);
                $this->entityManager->flush();

                return $content;
            }
        }

        throw new UserError($form->getErrors(true, true));
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

        // normalize data.
        if(is_array($args['data'])) {
            $args['data'] = IdentifierNormalizer::fromGraphQLData($args['data'], $this->fieldTypeManager, $content->getContentType());
        }

        // Update only changed fields on this entity. Note: nested values will get replaced, no recursively replacement possible here.
        $args['data'] = array_replace($content->getData(), $args['data']);

        // If mutations are performed via the main firewall instead of the api firewall, a csrf token must be passed to the form.
        if(is_array($args['data']) && !empty($context['csrf_token'])) {
            $args['data']['_token'] = $context['csrf_token'];
        }

        $form->submit($args['data']);

        if ($form->isSubmitted() && $form->isValid()) {

            $data = $form->getData();

            if (isset($data['locale'])) {
                $content->setLocale($data['locale']);
                unset($data['locale']);
            }

            $content->setData($data);

            // If content errors were found, map them to the form.
            $violations = $this->validator->validate($content);
            if (count($violations) > 0) {
                $violationMapper = new ViolationMapper();
                foreach ($violations as $violation) {
                    $violationMapper->mapViolation($violation, $form);
                }

            // If content is valid.
            } else {
                $this->entityManager->flush();
                return $content;
            }
        }

        throw new UserError($form->getErrors(true, true));
    }
}

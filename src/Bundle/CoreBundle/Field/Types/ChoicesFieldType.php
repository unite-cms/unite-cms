<?php

namespace UniteCMS\CoreBundle\Field\Types;

use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Security\Http\Firewall;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Constraints as Assert;
use UniteCMS\CoreBundle\Entity\FieldableContent;
use UniteCMS\CoreBundle\Entity\FieldableField;
use UniteCMS\CoreBundle\Field\FieldableFieldSettings;
use UniteCMS\CoreBundle\SchemaType\SchemaTypeManager;

class ChoicesFieldType extends ChoiceFieldType
{
    const TYPE = "choices";
    const SETTINGS = ['not_empty', 'description', 'default', 'choices', 'form_group'];

    /**
     * @var RequestContext $requestContext
     */
    protected $requestContext;

    public function __construct(RequestContext $requestContext)
    {
        $this->requestContext = $requestContext;
    }

    function getFormOptions(FieldableField $field): array
    {
        return array_merge(
            parent::getFormOptions($field),
            [
                'multiple' => true,

                // NOTE: This is not a good solution. In the future, we should pass context information to
                // getFormOptions, so we can return form options, based on the current context this form is built.
                'expanded' => substr($this->requestContext->getPathInfo(), -4) === '/api' ? false : true,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function validateDefaultValue($value, FieldableFieldSettings $settings, ExecutionContextInterface $context) {
        $context->getViolations()->addAll(
            $context->getValidator()->validate($value, new Assert\Type(['type' => 'array', 'message' => 'invalid_initial_data']))
        );
        if($context->getViolations()->count() == 0) {
            $context->getViolations()->addAll(
                $context->getValidator()->validate($value, new Assert\All(['constraints' => [
                    new Assert\Type(['type' => 'string', 'message' => 'invalid_initial_data']),
                    new Assert\Choice(['choices' => $settings->choices])
                ]]))
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    function alterData(FieldableField $field, &$data, FieldableContent $content, $rootData) {

        // Order entries by defined order in settings.
        if(!empty($field->getSettings()->choices) && !empty($data[$field->getIdentifier()])) {
            $data[$field->getIdentifier()] = array_intersect($field->getSettings()->choices, $data[$field->getIdentifier()]);
        }
    }

    /**
     * {@inheritdoc}
     */
    function getGraphQLType(FieldableField $field, SchemaTypeManager $schemaTypeManager)
    {
        return Type::listOf(Type::string());
    }

    /**
     * {@inheritdoc}
     */
    function getGraphQLInputType(FieldableField $field, SchemaTypeManager $schemaTypeManager)
    {
        return Type::listOf(Type::string());
    }

    /**
     * {@inheritdoc}
     */
    function resolveGraphQLData(FieldableField $field, $value, FieldableContent $content, array $args, $context, ResolveInfo $info)
    {
        return (array)$value;
    }

}

<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 2018-12-18
 * Time: 15:49
 */

namespace UniteCMS\CoreBundle\Field\Types;

use Doctrine\ORM\EntityRepository;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use UniteCMS\CoreBundle\Entity\ContentType;
use UniteCMS\CoreBundle\Entity\FieldableContent;
use UniteCMS\CoreBundle\Entity\FieldableField;
use UniteCMS\CoreBundle\Expression\ContentExpressionChecker;
use UniteCMS\CoreBundle\Field\FieldableFieldSettings;
use UniteCMS\CoreBundle\Form\AutoTextType;
use UniteCMS\CoreBundle\SchemaType\SchemaTypeManager;

class AutoTextFieldType extends TextFieldType
{
    const TYPE = "auto_text";
    const FORM_TYPE = AutoTextType::class;

    /**
     * All settings of this field type by key with optional default value.
     */
    const SETTINGS = ['expression', 'auto_update', 'text_widget', 'not_empty', 'description'];

    /**
     * All required settings for this field type.
     */
    const REQUIRED_SETTINGS = ['expression'];

    /**
     * @var Router $router
     */
    private $router;

    public function __construct(Router $router)
    {
        $this->router = $router;
    }

    function getFormOptions(FieldableField $field): array
    {
        return array_merge(
            parent::getFormOptions($field),
            [
                'expression' => $field->getSettings()->expression,
                'text_widget' => $field->getSettings()->text_widget === 'textarea' ? TextareaType::class : TextType::class,
                'auto_update' => !!$field->getSettings()->auto_update,
                'generation_url' => $this->router->generate(($field->getEntity() instanceof ContentType ? 'unitecms_core_content_preview' : 'unitecms_core_setting_preview'), [$field->getEntity()]),
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    function getDefaultValue(FieldableField $field)
    {
        return [
            'auto' => true,
            'text' => '',
        ];
    }

    /**
     * Generates the auto text value based on the current content object.
     *
     * @param FieldableField $field
     * @param FieldableContent $content
     * @return string
     */
    function generateAutoText(FieldableField $field, FieldableContent $content) {
        $expressionChecker = new ContentExpressionChecker();
        return $expressionChecker->evaluate($field->getSettings()->expression, $content);
    }

    /**
     * {@inheritdoc}
     */
    function getGraphQLType(FieldableField $field, SchemaTypeManager $schemaTypeManager, $nestingLevel = 0)
    {
        return $schemaTypeManager->getSchemaType('AutoTextField');
    }

    /**
     * {@inheritdoc}
     */
    function getGraphQLInputType(FieldableField $field, SchemaTypeManager $schemaTypeManager, $nestingLevel = 0)
    {
        return $schemaTypeManager->getSchemaType('AutoTextFieldInput');
    }

    /**
     * {@inheritdoc}
     */
    function resolveGraphQLData(FieldableField $field, $value, FieldableContent $content)
    {
        // We always return a regenerated text. This allows the to provide a preview and to compare with the stored text.
        return [
            'auto' => $value['auto'] ?? true,
            'text' => $value['text'] ?? '',
            'text_generated' => $this->generateAutoText($field, $content),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function onCreate(FieldableField $field, FieldableContent $content, EntityRepository $repository, &$data) {

        // If auto = true, generate value on create
        if(!empty($data[$field->getIdentifier()]['auto'])) {
            $data[$field->getIdentifier()]['text'] = $this->generateAutoText($field, $content);
        }
    }

    public function onUpdate(FieldableField $field, FieldableContent $content, EntityRepository $repository, $old_data, &$data) {

        // If auto = true and auto_update = true or old value was false, generate text
        if(!empty($data[$field->getIdentifier()]['auto'])) {
            if(($field->getSettings()->auto_update || empty($old_data[$field->getIdentifier()]['auto']))) {
                $data[$field->getIdentifier()]['text'] = $this->generateAutoText($field, $content);
            } else {
                $data[$field->getIdentifier()]['text'] = empty($old_data[$field->getIdentifier()]['text']) ? '' : $old_data[$field->getIdentifier()]['text'];
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    function validateSettings(FieldableFieldSettings $settings, ExecutionContextInterface $context)
    {
        // Validate allowed and required settings.
        parent::validateSettings($settings, $context);

        // Only continue, if there are no violations yet.
        if($context->getViolations()->count() > 0) {
            return;
        }

        $expressionChecker = new ContentExpressionChecker();
        if(!$expressionChecker->validate($settings->expression)) {
            $context->buildViolation('invalid_expression')->atPath('expression')->addViolation();
        }

        if(isset($settings->auto_update) && !is_bool($settings->auto_update)) {
            $context->buildViolation('noboolean_value')->atPath('auto_update')->addViolation();
        }

        if(isset($settings->text_widget) && (!is_string($settings->text_widget) || !in_array($settings->text_widget, ['text', 'textarea']))) {
            $context->buildViolation('invalid_auto_text_widget')->atPath('text_widget')->addViolation();
        }
    }
}

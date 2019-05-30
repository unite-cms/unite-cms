<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 2018-12-18
 * Time: 15:49
 */

namespace UniteCMS\CoreBundle\Field\Types;

use Doctrine\ORM\EntityManagerInterface;
use GraphQL\Type\Definition\ResolveInfo;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use UniteCMS\CollectionFieldBundle\Model\CollectionRow;
use UniteCMS\CoreBundle\Entity\Content;
use UniteCMS\CoreBundle\Entity\ContentType;
use UniteCMS\CoreBundle\Entity\Fieldable;
use UniteCMS\CoreBundle\Entity\FieldableContent;
use UniteCMS\CoreBundle\Entity\FieldableField;
use UniteCMS\CoreBundle\Entity\SettingType;
use UniteCMS\CoreBundle\Expression\UniteExpressionChecker;
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
    const SETTINGS = ['expression', 'auto_update', 'text_widget', 'not_empty', 'description', 'form_group'];

    /**
     * All required settings for this field type.
     */
    const REQUIRED_SETTINGS = ['expression'];

    /**
     * @var Router $router
     */
    private $router;

    /**
     * @var EntityManagerInterface $entityManager
     */
    private $entityManager;

    public function __construct(Router $router, EntityManagerInterface $entityManager)
    {
        $this->router = $router;
        $this->entityManager = $entityManager;
    }

    function getFormOptions(FieldableField $field): array
    {
        $generation_url = null;
        if($field->getEntity()->getRootEntity() instanceof ContentType) {
            $generation_url = $this->router->generate('unitecms_core_content_preview', [$field->getEntity()->getRootEntity()]);
        }

        else if($field->getEntity()->getRootEntity() instanceof SettingType) {
            $generation_url = $this->router->generate('unitecms_core_setting_preview', [$field->getEntity()->getRootEntity()]);
        }

        return array_merge(
            parent::getFormOptions($field),
            [
                'expression' => $field->getSettings()->expression,
                'text_widget' => $field->getSettings()->text_widget === 'textarea' ? TextareaType::class : TextType::class,
                'auto_update' => !!$field->getSettings()->auto_update,
                'generation_url' => $generation_url,
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
     * @param $content_id
     * @return string
     */
    function generateAutoText(FieldableField $field, FieldableContent $content, $content_id) {
        $expressionChecker = new UniteExpressionChecker();
        $expressionChecker->registerVariable('id', $content_id);

        if($content instanceof Content) {
            $expressionChecker->registerDoctrineContentFunctionsProvider($this->entityManager, $content->getContentType());
        }

        return $expressionChecker
            ->registerFieldableContent($content)
            ->evaluateToString($field->getSettings()->expression);
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
    function resolveGraphQLData(FieldableField $field, $value, FieldableContent $content, array $args, $context, ResolveInfo $info)
    {
        if(!$content instanceof Content && $content->getRootFieldableContent() instanceof Content) {
            $use_content = $content->getRootFieldableContent();
        } else {
            $use_content = $content;
        }

        // We always return a regenerated text. This allows the to provide a preview and to compare with the stored text.
        return [
            'auto' => $value['auto'] ?? true,
            'text' => $value['text'] ?? '',
            'text_generated' => $this->generateAutoText($field, $use_content, $content->getId()),
        ];
    }

    /**
     * {@inheritdoc}
     */
    function alterData(FieldableField $field, &$data, FieldableContent $content, $rootData)
    {
        if(empty($data[$field->getIdentifier()])) {
            $data[$field->getIdentifier()] = $this->getDefaultValue($field);
        }

        if(!empty($data[$field->getIdentifier()]['auto'])) {

            if(($field->getSettings()->auto_update || empty($content->getData()[$field->getIdentifier()]['auto']))) {

                $content_id = $content->getId();

                if(!$content instanceof Content && $content->getRootFieldableContent() instanceof Content) {
                    $tmp_content = clone $content->getRootFieldableContent();
                } else {
                    $tmp_content = clone $content;
                }

                $tmp_content->setData($rootData);
                $data[$field->getIdentifier()]['text'] = $this->generateAutoText($field, $tmp_content, $content_id);
                unset($tmp_content);

            } else {
                $data[$field->getIdentifier()]['text'] = empty($content->getData()[$field->getIdentifier()]['text']) ? '' : $content->getData()[$field->getIdentifier()]['text'];
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

        if(!$context->getObject()->getEntity()->getRootEntity() instanceof ContentType && !$context->getObject()->getEntity()->getRootEntity()  instanceof SettingType) {
            $context->buildViolation('invalid_entity_type')->addViolation();
        }

        $expressionChecker = new UniteExpressionChecker();
        $expressionChecker->registerFieldableContent(null);

        if($context->getObject()->getEntity() instanceof Fieldable) {
            $expressionChecker->registerVariable('id');
        }

        if($context->getObject()->getEntity()->getRootEntity() instanceof ContentType) {
            $expressionChecker->registerDoctrineContentFunctionsProvider($this->entityManager, new ContentType());
        }

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

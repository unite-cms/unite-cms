<?php

namespace UniteCMS\CoreBundle\View\Types;

use Symfony\Component\Validator\Context\ExecutionContextInterface;
use UniteCMS\CoreBundle\Entity\View;
use UniteCMS\CoreBundle\SchemaType\IdentifierNormalizer;
use UniteCMS\CoreBundle\View\ViewSettings;
use UniteCMS\CoreBundle\View\ViewType;

class SortableViewType extends ViewType
{
    const TYPE = "sortable";
    const TEMPLATE = "UniteCMSCoreBundle:Views:Sortable/index.html.twig";

    const SETTINGS = [
        'columns',
        'sort_field',
    ];

    const REQUIRED_SETTINGS = [
        'sort_field',
    ];

    /**
     * {@inheritdoc}
     */
    function getTemplateRenderParameters(View $view, string $selectMode = self::SELECT_MODE_NONE): array
    {
        $columns = $view->getSettings()->columns ?? [];
        $sort_field = $view->getSettings()->sort_field;

        // If no columns are defined, try to find any human readable key identifier and also add common fields.
        $fields = $view->getContentType()->getFields();
        $possible_field_types = ['text'];

        if (empty($columns)) {
            if ($fields->containsKey('title') && in_array($fields->get('title')->getType(), $possible_field_types)) {
                $columns['title'] = 'Title';
            } elseif ($fields->containsKey('name') && in_array(
                    $fields->get('name')->getType(),
                    $possible_field_types
                )) {
                $columns['name'] = 'Name';
            } else {
                $columns['id'] = 'ID';
            }

            $columns['created'] = 'Created';
            $columns['updated'] = 'Updated';
        }

        return [
            'sort_field' => $sort_field,
            'columns' => $columns,
            'View' => $view->getIdentifier(),
            'contentType' => IdentifierNormalizer::graphQLIdentifier($view->getContentType()),
            'hasTranslations' => count($view->getContentType()->getLocales()) > 1,
        ];
    }

    /**
     * {@inheritdoc}
     */
    function validateSettings(ViewSettings $settings, ExecutionContextInterface $context)
    {
        parent::validateSettings($settings, $context);

        // Only continue, if all required settings are available and there are no additional settings.
        if ($context->getViolations()->count() > 0) {
            return;
        }

        /**
         * @var View $view
         */
        $view = $context->getObject();

        // validate setting structure.
        if (!empty($settings->columns) && !is_array($settings->columns)) {
            $context->buildViolation('wrong_setting_definition')->atPath('columns')->addViolation();
        }
        if (!empty($settings->sort_field) && !is_string($settings->sort_field)) {
            $context->buildViolation('wrong_setting_definition')->atPath('sort_field')->addViolation();
        }

        // Only continue, if all setting properties have correct type.
        if ($context->getViolations()->count() > 0) {
            return;
        }

        // Validate column fields.
        if (!empty($settings->columns)) {
            foreach ($settings->columns as $field => $label) {
                if (!$this->content_type_contains_field($view, $field)) {
                    $context->buildViolation('unknown_column')->atPath('columns.'.$field)->addViolation();
                }
            }
        }

        // Validate sort_field.
        if (!empty($settings->sort_field)) {
            if (!$this->content_type_contains_field($view, $settings->sort_field)) {
                $context->buildViolation('unknown_column')->atPath('sort_field')->addViolation();
            }
        }
    }

    private function content_type_contains_field(View $view, $field)
    {
        if (in_array($field, ['id', 'locale', 'created', 'updated', 'deleted'])) {
            return true;
        }

        // At the moment we just check the root field. In the future we could also check nested field properties here.
        $fieldParts = explode('.', $field);
        $field = array_shift($fieldParts);

        return $view->getContentType()->getFields()->containsKey($field);
    }
}

<?php

namespace UnitedCMS\CoreBundle\View\Types;

use Symfony\Component\Validator\ConstraintViolation;
use UnitedCMS\CoreBundle\View\ViewSettings;
use UnitedCMS\CoreBundle\View\ViewType;

class SortableViewType extends ViewType
{
    const TYPE = "sortable";
    const TEMPLATE = "UnitedCMSCoreBundle:Views:Sortable/index.html.twig";

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
    function getTemplateRenderParameters(string $selectMode = self::SELECT_MODE_NONE): array
    {
        $columns = $this->view->getSettings()->columns ?? [];
        $sort_field = $this->view->getSettings()->sort_field;

        // If no columns are defined, try to find any human readable key identifier and also add common fields.
        $fields = $this->view->getContentType()->getFields();
        $possible_field_types = ['text'];

        if (empty($columns)) {
            if ($fields->containsKey('title') && in_array($fields->get('title')->getType(), $possible_field_types)) {
                $columns['title'] = 'Title';
            }

            elseif ($fields->containsKey('name') && in_array($fields->get('name')->getType(), $possible_field_types)) {
                $columns['name'] = 'Name';
            }

            else {
                $columns['id'] = 'ID';
            }

            $columns['created'] = 'Created';
            $columns['updated'] = 'Updated';
        }

        return [
            'sort_field' => $sort_field,
            'columns' => $columns,
            'View' => $this->view->getIdentifier(),
            'contentType' => $this->view->getContentType()->getIdentifier(),
            'hasTranslations' => count($this->view->getContentType()->getLocales()) > 1,
        ];
    }

    /**
     * {@inheritdoc}
     */
    function validateSettings(ViewSettings $settings): array
    {
        $violations = parent::validateSettings($settings);

        // Only continue, if all required settings are available and there are no additional settings.
        if(!empty($violations)) {
            return $violations;
        }

        // validate setting structure.
        if(!empty($settings->columns) && !is_array($settings->columns)) {
            $violations[] = $this->createInvalidSettingDefinitionConstraint($settings,'columns');
        }
        if(!empty($settings->sort_field) && !is_string($settings->sort_field)) {
            $violations[] = $this->createInvalidSettingDefinitionConstraint($settings,'sort_field');
        }

        // Only continue, if all setting properties have correct type.
        if(!empty($violations)) {
            return $violations;
        }

        // Validate column fields.
        if(!empty($settings->columns)) {
            foreach ($settings->columns as $field => $label) {
                if (!$this->content_type_contains_field($field)) {
                    $violations[] = $this->createUnknownColumnConstraint($settings, 'columns.' . $field);
                }
            }
        }

        // Validate sort_field.
        if(!empty($settings->sort_field)) {
            if(!$this->content_type_contains_field($settings->sort_field)) {
                $violations[] = $this->createUnknownColumnConstraint($settings, 'sort_field');
            }
        }

        return $violations;
    }

    private function createInvalidSettingDefinitionConstraint($settings, $property) {
        return new ConstraintViolation(
            "validation.invalid_{$property}_definition",
            "validation.invalid_{$property}_definition",
            [],
            $settings,
            $property,
            $settings
        );
    }

    private function createUnknownColumnConstraint($settings, $property_path) {
        return new ConstraintViolation(
            "validation.unknown_column",
            "validation.unknown_column",
            [],
            $settings,
            $property_path,
            $settings
        );
    }

    private function content_type_contains_field($field) {
        if(in_array($field, ['id', 'locale', 'created', 'updated', 'deleted'])) {
            return true;
        }
        return $this->view->getContentType()->getFields()->containsKey($field);
    }
}
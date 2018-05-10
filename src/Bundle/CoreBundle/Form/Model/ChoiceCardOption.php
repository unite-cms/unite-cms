<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 10.05.18
 * Time: 15:17
 */

namespace UniteCMS\CoreBundle\Form\Model;


class ChoiceCardOption
{
    private $value;

    private $label;

    private $description;

    private $icon;

    public function __construct($value, string $label, string $description, string $icon)
    {
        $this->value = $value;
        $this->label = $label;
        $this->description = $description;
        $this->icon = $icon;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @return string
     */
    public function getLabel(): string
    {
        return $this->label;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @return string
     */
    public function getIcon(): string
    {
        return $this->icon;
    }
}
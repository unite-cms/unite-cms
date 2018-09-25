<?php
/**
 * Created by PhpStorm.
 * User: stefankamsker
 * Date: 19.09.18
 * Time: 15:16
 */

namespace UniteCMS\CoreBundle\Model;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * We use this model only for validation!
 */
class StatePlace
{
    /**
     * @var string
     * @Assert\Type(type="string", message="workflow_invalid_place")
     * @Assert\NotBlank(message="not_blank")
     */
    private $identifier;

    /**
     * @var string
     * @Assert\Type(type="string", message="workflow_invalid_place")
     * @Assert\NotBlank(message="not_blank")
     */
    private $label;

    /**
     * @var string
     * @Assert\Choice(choices={"primary", "notice", "info", "success", "warning", "error", "danger"}, message="workflow_invalid_category")
     */
    private $category;
    
    public function __construct($identifier, $label, $category = null)
    {
        $this->identifier = $identifier;
        $this->label = $label;
        $this->category = $category;
    }

    /**
     * Get identifier
     *
     * @return string
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * @param string $identifier
     *
     * @return StatePlace
     */
    public function setIdentifier($identifier)
    {
        $this->identifier = $identifier;

        return $this;
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @param string label
     *
     * @return StatePlace
     */
    public function setLabel($label)
    {
        $this->$label = $label;

        return $this;
    }

    /**
     * @return string
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * @param string $category
     *
     * @return StatePlace
     */
    public function setCategory($category)
    {
        $this->category = $category;

        return $this;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->label;
    }

}
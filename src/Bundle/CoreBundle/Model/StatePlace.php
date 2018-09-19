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
     * @Assert\Type(type="string", message="invalid_places")
     * @Assert\NotBlank(message="invalid_places")
     */
    private $identifier;

    /**
     * @var string
     * @Assert\Type(type="string", message="invalid_places")
     * @Assert\Choice(choices={"", "primary", "notice", "info", "success", "warning", "error", "danger"}, message="invalid_place_category")
     */
    private $category;
    
    public function __construct($identifier, $category = null)
    {
        $this->identifier = $identifier;
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

}
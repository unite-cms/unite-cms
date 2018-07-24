<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 24.07.18
 * Time: 10:12
 */

namespace UniteCMS\CoreBundle\Field;

use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Type;

/**
 * @ExclusionPolicy("none")
 */
class FieldableValidation
{
    /**
     * @var string
     *
     * @Type("string")
     */
    private $expression;

    /**
     * @var string
     *
     * @Type("string")
     */
    private $message;

    /**
     * @var string
     *
     * @Type("string")
     */
    private $path;

    /**
     * @var string[]
     *
     * @Type("array<string>")
     */
    private $groups;

    public function __construct(string $expression, string $message = '', string $path = '', array $groups = ['CREATE', 'UPDATE'])
    {
        $this->expression = $expression;
        $this->message = $message;
        $this->path = $path;
        $this->groups = $groups;
    }

    /**
     * @return string
     */
    public function getExpression(): string
    {
        return $this->expression;
    }

    /**
     * @param string $expression
     */
    public function setExpression(string $expression): void
    {
        $this->expression = $expression;
    }

    /**
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * @param string $message
     */
    public function setMessage(string $message): void
    {
        $this->message = $message;
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * @param string $path
     */
    public function setPath(string $path): void
    {
        $this->path = $path;
    }

    /**
     * @return string[]
     */
    public function getGroups(): array
    {
        return $this->groups;
    }

    /**
     * @param string[] $groups
     */
    public function setGroups(array $groups): void
    {
        $this->groups = $groups;
    }
}
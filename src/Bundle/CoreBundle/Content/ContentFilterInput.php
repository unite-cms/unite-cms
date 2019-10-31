<?php


namespace UniteCMS\CoreBundle\Content;


class ContentFilterInput
{

    /**
     * @var null|string
     */
    protected $field;

    /**
     * @var null|string
     */
    protected $value;

    /**
     * @var ContentFilterInput[]
     */
    protected $AND;

    /**
     * @var ContentFilterInput[]
     */
    protected $OR;

    public function __construct($field = null, $value = null, array $AND = [], array $OR = [])
    {
        $this->field = $field;
        $this->value = $value;
        $this->AND = $AND;
        $this->OR = $OR;
    }

    static function fromInput(array $input) : self {
        $input = new self(
            $input['field'] ?? null,
            $input['value'] ?? null,
            empty($input['AND']) ? [] : array_map('static::fromInput', $input['AND']),
            empty($input['OR']) ? [] : array_map('static::fromInput', $input['OR'])
        );
        return $input;
    }

    /**
     * @return null|string
     */
    public function getField() : ?string
    {
        return $this->field;
    }

    /**
     * @return null|string
     */
    public function getValue() : ?string
    {
        return $this->value;
    }

    /**
     * @return ContentFilterInput[]
     */
    public function getAND(): array
    {
        return $this->AND;
    }

    /**
     * @return ContentFilterInput[]
     */
    public function getOR(): array
    {
        return $this->OR;
    }
}

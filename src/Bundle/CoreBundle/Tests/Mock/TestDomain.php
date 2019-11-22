<?php


namespace UniteCMS\CoreBundle\Tests\Mock;

use UniteCMS\CoreBundle\Domain\Domain;

class TestDomain extends Domain
{
    /**
     * @var array $extraSchema
     */
    protected $extraSchema = [];

    public function setExtraSchema(array $extraSchema = []) : self {
        $this->extraSchema = $extraSchema;
        return $this;
    }

    /**
     * @return string[]
     */
    public function getSchema() : array
    {
        return array_merge($this->schema, $this->extraSchema);
    }
}

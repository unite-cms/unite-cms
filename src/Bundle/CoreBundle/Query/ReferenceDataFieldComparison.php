<?php


namespace UniteCMS\CoreBundle\Query;

class ReferenceDataFieldComparison extends DataFieldComparison {

    /**
     * @var string $referencedType
     */
    protected $referencedType;

    /**
     * @var array $referencedPath
     */
    protected $referencedPath;

    public function __construct($field, $operator, $value, array $path = ['data'], string $referencedType, array $referencedPath)
    {
        $this->referencedType = $referencedType;
        $this->referencedPath = $referencedPath;

        if(count($this->referencedPath) === 1 && !in_array($this->referencedPath[0], ContentCriteria::BASE_FIELDS)) {
            $this->referencedPath[] = 'data';
        }

        parent::__construct($field, $operator, $value, $path);
    }

    /**
     * @return string
     */
    public function getReferencedType(): string
    {
        return $this->referencedType;
    }

    /**
     * @return array
     */
    public function getReferencedPath() : array {
        return array_merge($this->referencedPath);
    }

    /**
     * {@inheritDoc}
     */
    public function getField()
    {
        return join('.', array_merge($this->referencedPath));
    }
}

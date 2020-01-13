<?php


namespace UniteCMS\CoreBundle\Query;

use UniteCMS\CoreBundle\Content\ContentInterface;

class ReferenceDataFieldComparison extends DataFieldComparison {

    /**
     * @var string $referencedType
     */
    protected $referencedType;

    /**
     * @var array $referencedPath
     */
    protected $referencedPath;

    /**
     * @var string $entity
     */
    protected $entity;

    public function __construct($field, $operator, $value, array $path = ['data'], string $referencedType, array $referencedPath, string $entity = ContentInterface::class)
    {
        $this->referencedType = $referencedType;
        $this->referencedPath = $referencedPath;
        $this->entity = $entity;

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

    /**
     * @return string
     */
    public function getEntity() : string
    {
        return $this->entity;
    }
}

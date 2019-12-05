<?php


namespace UniteCMS\MediaBundle\Flysystem;

use Ramsey\Uuid\Uuid;

class UploadToken
{
    /**
     * @var string
     */
    protected $id;

    /**
     * @var string
     */
    protected $filename;

    /**
     * @var string
     */
    protected $tmpUploadUrl;

    /**
     * @var string
     */
    protected $driver;

    /**
     * @var string $type
     */
    protected $type;

    /**
     * @var string $field
     */
    protected $field;

    public function __construct(?string $id = null, ?string $filename = null, ?string $tmpUploadUrl = null, ?string $driver = null, ?string $type = null, ?string $field = null)
    {
        $this->id = $id ?? Uuid::uuid4();
        $this->filename = $filename;
        $this->tmpUploadUrl = $tmpUploadUrl;
        $this->driver = $driver;
        $this->type = $type;
        $this->field = $field;
    }

    /**
     * @param array $data
     *
     * @return UploadToken
     */
    static function fromArray(array $data) : self {
        return new self($data['i'], $data['f'], $data['u'], $data['d'], $data['t'], $data['fi']);
    }

    /**
     * @return array
     */
    public function toArray() : array {
        return [
            'i' => $this->getId(),
            'f' => $this->getFilename(),
            'u' => $this->getTmpUploadUrl(),
            'd' => $this->getDriver(),
            't' => $this->getType(),
            'fi' => $this->getField(),
        ];
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getFilename(): string
    {
        return $this->filename;
    }

    /**
     * @param string $filename
     * @return self
     */
    public function setFilename(string $filename): self
    {
        $this->filename = $filename;
        return $this;
    }

    /**
     * @return string
     */
    public function getTmpUploadUrl(): string
    {
        return $this->tmpUploadUrl;
    }

    /**
     * @param string $tmpUploadUrl
     * @return self
     */
    public function setTmpUploadUrl(string $tmpUploadUrl): self
    {
        $this->tmpUploadUrl = $tmpUploadUrl;
        return $this;
    }

    /**
     * @return string
     */
    public function getDriver(): string
    {
        return $this->driver;
    }

    /**
     * @param string $driver
     * @return self
     */
    public function setDriver(string $driver): self
    {
        $this->driver = $driver;
        return $this;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     * @return self
     */
    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @return string
     */
    public function getField(): string
    {
        return $this->field;
    }

    /**
     * @param string $field
     * @return self
     */
    public function setField(string $field): self
    {
        $this->field = $field;
        return $this;
    }
}

<?php


namespace UniteCMS\CoreBundle\Security\User;

use UniteCMS\CoreBundle\Content\BaseContent;
use UniteCMS\CoreBundle\Content\ContentInterface;
use UniteCMS\CoreBundle\Content\FieldData;
use UniteCMS\CoreBundle\Content\SensitiveFieldData;

abstract class BaseUser extends BaseContent implements UserInterface
{
    /**
     * @var string|null
     */
    protected $username;

    /**
     * @var FieldData[]
     */
    protected $sensitiveData = [];

    /**
     * @var array
     */
    protected $tokens = [];

    /**
     * @var bool $fullyAuthenticated
     */
    protected $fullyAuthenticated = false;

    /**
     * {@inheritDoc}
     */
    public function setFullyAuthenticated(bool $fullyAuthenticated = true): void
    {
        $this->fullyAuthenticated = $fullyAuthenticated;
    }

    /**
     * {@inheritDoc}
     */
    public function isFullyAuthenticated(): bool
    {
        return $this->fullyAuthenticated;
    }

    /**
     * {@inheritDoc}
     */
    public function getData(): array
    {
        if(!is_array($this->sensitiveData)) {
            $this->sensitiveData = [];
        }

        return (parent::getData() + $this->sensitiveData + ['username' => new FieldData($this->getUsername())]);
    }

    /**
     * @param array $data
     * @return self
     */
    public function setData(array $data) : ContentInterface
    {
        $this->data = [];
        $this->sensitiveData = [];

        foreach($data as $name => $value) {
            if($name === 'username') {
                $this->username = $value instanceof FieldData ? $value->resolveData() : $value;
            }

            else if ($value instanceof SensitiveFieldData) {
                $this->sensitiveData[$name] = $value;
            }

            else {
                $this->data[$name] = $value;
            }
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getFieldData(string $fieldName): ?FieldData
    {
        return $fieldName === 'username' ?
            new FieldData($this->getUsername()) :
            (isset($this->getData()[$fieldName]) ? $this->getData()[$fieldName] : null);
    }

    /**
     * {@inheritDoc}
     */
    public function getUsername() : ?string {
        return $this->username;
    }

    /**
     * {@inheritDoc}
     */
    public function getRoles()
    {
        return [sprintf('ROLE_%s', strtoupper($this->getType()))];
    }

    /**
     * {@inheritDoc}
     */
    public function getPassword()
    {
        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function getSalt()
    {
        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function eraseCredentials() {}

    /**
     * {@inheritDoc}
     */
    public function getToken(string $key) : ?string {
        return $this->tokens[$key] ?? null;
    }

    /**
     * {@inheritDoc}
     */
    public function setToken(string $key, ?string $token = null) : void {
        $this->tokens[$key] = $token;
    }
}

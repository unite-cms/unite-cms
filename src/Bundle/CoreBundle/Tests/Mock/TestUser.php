<?php


namespace UniteCMS\CoreBundle\Tests\Mock;

use UniteCMS\CoreBundle\Content\FieldData;
use UniteCMS\CoreBundle\Security\User\UserInterface;

class TestUser extends TestContent implements UserInterface
{
    public $username;
    public $tokens = [];
    public $fullyAuthenticated = false;

    /**
     * {@inheritDoc}
     */
    public function getRoles()
    {
        return ['ROLE_' . strtoupper($this->getType())];
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
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * {@inheritDoc}
     */
    public function eraseCredentials(){}

    /**
     * @param array $data
     *
     * @return $this
     */
    public function setData(array $data) : TestContent
    {
        if(isset($data['username'])) {
            $this->username = (string)$data['username'];
            unset($data['username']);
        }

        $this->data = $data;
        return $this;
    }

    /**
     * @return FieldData[]
     */
    public function getData(): array
    {
        if(!is_array($this->data)) {
            $this->data = [];
        }

        return ($this->data + ['username' => new FieldData($this->getUsername()) ]);
    }

    /**
     * {@inheritDoc}
     */
    public function getFieldData(string $fieldName): ?FieldData
    {
        return $fieldName === 'username' ?
            new FieldData($this->getUsername()) :
            parent::getFieldData($fieldName);
    }

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
}

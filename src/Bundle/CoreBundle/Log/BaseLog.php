<?php


namespace UniteCMS\CoreBundle\Log;

use DateTime;

abstract class BaseLog implements LogInterface
{

    /**
     * @var string $level
     */
    protected $level;

    /**
     * @var string $message
     */
    protected  $message;

    /**
     * @var DateTime $created
     */
    protected  $created;

    /**
     * @var string
     */
    protected  $username = null;

    /**
     * BaseLog constructor.
     *
     * @param string $level
     * @param string $message
     * @param string|null $username
     */
    public function __construct(string $level, string $message, ?string $username = null) {
        $this->level = $level;
        $this->message = $message;
        $this->username = $username;
        $this->created = new DateTime('now');
    }

    /**
     * @return mixed
     */
    public function getLevel() : string
    {
        return $this->level;
    }

    /**
     * @param mixed $level
     * @return self
     */
    public function setLevel($level): LogInterface
    {
        $this->level = $level;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getMessage() : string
    {
        return $this->message;
    }

    /**
     * @param mixed $message
     * @return self
     */
    public function setMessage($message): LogInterface
    {
        $this->message = $message;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getUsername() : ?string
    {
        return $this->username;
    }

    /**
     * @param mixed $username
     * @return self
     */
    public function setUsername($username): LogInterface
    {
        $this->username = $username;
        return $this;
    }

    /**
     * @return DateTime
     */
    public function getCreated(): DateTime
    {
        return $this->created;
    }

    /**
     * @param DateTime $created
     * @return self
     */
    public function setCreated(DateTime $created): LogInterface
    {
        $this->created = $created;
        return $this;
    }
}

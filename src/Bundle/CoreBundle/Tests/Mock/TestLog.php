<?php


namespace UniteCMS\CoreBundle\Tests\Mock;

use DateTime;
use UniteCMS\CoreBundle\Log\LogInterface;

class TestLog implements LogInterface
{
    public $level;
    public $message;
    public $created;
    public $username = null;

    public function __construct(string $level, string $message, ?string $username = null) {
        $this->level = $level;
        $this->message = $message;
        $this->username = $username;
        $this->created = new DateTime();
    }

    public function getLevel(): string
    {
        return $this->level;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function getCreated(): DateTime
    {
        return $this->created;
    }
}

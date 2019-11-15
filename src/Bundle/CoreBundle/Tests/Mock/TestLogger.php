<?php


namespace UniteCMS\CoreBundle\Tests\Mock;

use DateTime;
use UniteCMS\CoreBundle\Domain\Domain;
use UniteCMS\CoreBundle\Log\LoggerInterface;
use UniteCMS\CoreBundle\Log\LogInterface;

class TestLogger implements LoggerInterface
{
    protected $logs = [];

    /**
     * {@inheritDoc}
     */
    public function log(Domain $domain, string $level, string $message, string $username = null): LogInterface {
        $log = new TestLog($level, $message, $username);
        if(!isset($this->logs[$domain->getId()])) {
            $this->logs[$domain->getId()] = [];
        }
        $this->logs[$domain->getId()][] = $log;
        return $log;
    }

    /**
     * {@inheritDoc}
     */
    public function getLogs(Domain $domain, DateTime $before, DateTime $after = null, int $limit = 100, int $offset = 0): array {
        if(!isset($this->logs[$domain->getId()])) {
            $this->logs[$domain->getId()] = [];
        }
        return $this->logs[$domain->getId()];
    }
}

<?php

namespace UniteCMS\CoreBundle\Log;

use DateTime;
use UniteCMS\CoreBundle\Domain\Domain;

interface LoggerInterface
{
    const NOTICE = 'NOTICE';
    const WARNING = 'WARNING';
    const ERROR = 'ERROR';
    const CRITICAL = 'CRITICAL';
    const EMERGENCY = 'EMERGENCY';
    const LEVELS = [
        self::NOTICE,
        self::WARNING,
        self::ERROR,
        self::CRITICAL,
        self::EMERGENCY,
    ];

    /**
     * @param Domain $domain
     * @param string $level
     * @param string $message
     * @param string $username
     *
     * @return LogInterface
     */
    public function log(Domain $domain, string $level, string $message, string $username = null) : LogInterface;

    /**
     * @param Domain $domain
     * @param DateTime $before
     * @param DateTime $after
     * @param int $limit
     * @param int $offset
     *
     * @return LogInterface[]
     */
    public function getLogs(Domain $domain, DateTime $before, DateTime $after = null, int $limit = 100, int $offset = 0) : array;
}

<?php

namespace UniteCMS\CoreBundle\Monolog;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use UniteCMS\CoreBundle\Domain\DomainManager;

class DomainStreamHandler extends StreamHandler
{
    /**
     * @var DomainManager $domainManager
     */
    protected $domainManager;

    protected $streamPattern;
    protected $stream;
    protected $url;
    protected $filePermission;
    protected $useLocking;

    public function __construct(string $streamPattern, DomainManager $domainManager, $level = Logger::DEBUG, $bubble = true, $filePermission = null, $useLocking = false)
    {
        $this->streamPattern = $streamPattern;
        $stream = str_replace('{domain}', '_fallback', $this->streamPattern);
        parent::__construct($stream, $level, $bubble, $filePermission, $useLocking);
        $this->domainManager = $domainManager;
    }

    /**
     * {@inheritdoc}
     */
    protected function write(array $record)
    {
        $this->url = str_replace('{domain}', $this->domainManager->current()->getId(), $this->streamPattern);
        parent::write($record);
    }
}

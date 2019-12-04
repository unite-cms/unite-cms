<?php

namespace UniteCMS\MediaBundle\Flysystem;

use UniteCMS\MediaBundle\Flysystem\FlySystemInterface;
use UniteCMS\MediaBundle\Flysystem\Plugin\S3PresignedUrl;
use League\Flysystem\AwsS3v3\AwsS3Adapter;
use League\Flysystem\Filesystem;
use Aws\S3\S3Client;

class S3FlySystem extends FlySystemManager implements FlySystemInterface {

    /**
     * @var array $config;
     */
    protected $config;

    /**
     * @var S3Client $client;
     */
    protected $client;

    /**
     * @var Filesystem $filesystem;
     */
    protected $filesystem;

    public function __construct(array $config) {
        $this->config = $config;
    }

    public function getInstance(): FlySystemInterface {
       $this->client = new S3Client([
           'credentials' => [
               'key' => $this->config['key'],
               'secret' => $this->config['secret']
           ],
           'region' => $this->config['region'],
           'version' => $this->config['version'],
           'endpoint' => $this->config['endpoint'],
       ]);

       $adapter = new AwsS3Adapter($this->client, $this->config['bucket']);
       $this->filesystem = new Filesystem($adapter);
       $this->filesystem->addPlugin(new S3PresignedUrl());
       return $this;
    }

    public function getFileSystem() {
       return $this->filesystem;
    }

    public function getClient() {
       return $this->client;
    }

    public function getPresignedUrl(): string {
        return $this->filesystem->getPresignedUrl($this->config['path']);
    }
}
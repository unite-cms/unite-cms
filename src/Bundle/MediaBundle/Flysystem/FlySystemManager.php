<?php

namespace UniteCMS\MediaBundle\FlySystem;

use UniteCMS\MediaBundle\FlySystem\Plugin\S3PresignedUrl;
use League\Flysystem\AwsS3v3\AwsS3Adapter;
use League\Flysystem\Filesystem;
use Aws\S3\S3Client;

class FlySystemManager
{
    /**
     * @var array $config;
     */
    protected $config;

    /**
     * @var string $driver;
     */
    protected $driver;

    /**
     * @var S3Client $client;
     */
    protected $client;

    /**
     * @var Filesystem $filesystem;
     */
    protected $filesystem;

    public function initiate(array $config) {
        $this->config = $config;
        $this->client = new S3Client([
            'credentials' => [
                'key'    => $this->config['key'],
                'secret' => $this->config['secret']
            ],
            'region' => $this->config['region'],
            'version' => $this->config['version'],
            'endpoint' => $this->config['endpoint'],
        ]);

        $adapter = new AwsS3Adapter($this->client, $this->config['bucket']);
        $this->filesystem = new Filesystem($adapter);
        $this->filesystem->addPlugin(new S3PresignedUrl());
    }

    public function getPresignedUrl() {
        return $this->filesystem->getPresignedUrl($this->config['path']);
    }

    public function saveFile() {

    }

    public function deleteFile() {

    }
}
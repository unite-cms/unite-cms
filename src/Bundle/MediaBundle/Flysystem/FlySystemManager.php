<?php

namespace UniteCMS\MediaBundle\Flysystem;

use Aws\S3\S3Client;
use Google\Cloud\Storage\StorageClient;
use InvalidArgumentException;
use League\Flysystem\Adapter\Local;
use League\Flysystem\AwsS3v3\AwsS3Adapter;
use League\Flysystem\Filesystem;
use Superbalist\Flysystem\GoogleStorage\GoogleStorageAdapter;
use UniteCMS\MediaBundle\Flysystem\Plugin\DownloadFile;
use UniteCMS\MediaBundle\Flysystem\Plugin\UploadFile;

class FlySystemManager
{
    /**
     * @var array Fil
     */
    protected $systems = [];

    /**
     * Create a filesystem for the given driver and config.
     *
     * @param string $driver
     * @param array $config
     *
     * @return Filesystem
     */
    public function createFilesystem(string $driver, array $config = []) : Filesystem {
        $filesystem = null;
        $systemKey = md5($driver . serialize($config));

        if(!empty($this->systems[$systemKey])) {
            return $this->systems[$systemKey];
        }

        $adapter = null;

        switch ($driver) {
            case 'local':
                $adapter = new Local(__DIR__.'/path/to/root');
                break;
            case 's3':
                $client = new S3Client([
                    'credentials' => [
                        'key'    => $config['key'],
                        'secret' => $config['secret'],
                    ],
                    'region' => $config['region'],
                    'endpoint' => $config['endpoint'],
                    'version' => $config['version'] ?? 'latest',
                    'use_path_style_endpoint' => $config['use_path_style_endpoint'] ?? false,
                ]);
                $adapter = new AwsS3Adapter($client, $config['bucket']);
                break;
            case 'google':
                $client = new StorageClient([
                    'projectId' => $config['id'],
                ]);
                $bucket = $client->bucket($config['bucket']);
                $adapter = new GoogleStorageAdapter($client, $bucket);
                break;
            default:
                throw new InvalidArgumentException(sprintf('No filesystem driver "%s" found', $driver));
            break;
        }

        $this->systems[$systemKey] = new Filesystem($adapter);
        $this->systems[$systemKey]->addPlugin(new UploadFile());
        $this->systems[$systemKey]->addPlugin(new DownloadFile());
        
        return $this->systems[$systemKey];
    }
}

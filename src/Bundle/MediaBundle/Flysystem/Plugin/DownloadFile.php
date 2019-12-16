<?php


namespace UniteCMS\MediaBundle\Flysystem\Plugin;

use InvalidArgumentException;
use League\Flysystem\Adapter\Local;
use League\Flysystem\AwsS3v3\AwsS3Adapter;
use League\Flysystem\FilesystemInterface;
use League\Flysystem\PluginInterface;
use Superbalist\Flysystem\GoogleStorage\GoogleStorageAdapter;

class DownloadFile implements PluginInterface
{
    /**
     * FilesystemInterface instance.
     *
     * @var FilesystemInterface
     */
    protected $filesystem;

    /**
     * {@inheritDoc}
     */
    public function getMethod()
    {
        return 'getDownloadUrl';
    }

    /**
     * {@inheritDoc}
     */
    public function setFilesystem(FilesystemInterface $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    /**
     * Get a download url to a file, optionally pre-signed
     *
     * @param string $id
     * @param string $filename
     * @param bool $preSign
     * @param array $config
     *
     * @return UploadFile
     */
    public function handle(string $id, string $filename, bool $preSign = false, array $config = []) : String
    {
        $adapter = $this->filesystem->getAdapter();

        if($adapter instanceof AwsS3Adapter) {

            $options = [
                'Bucket' => $adapter->getBucket(),
                'Key' => $adapter->applyPathPrefix(($config['path'] ?? '') . '/' . $id . '/' . $filename),
            ];

            $S3Client = $adapter->getClient();
            $command = $S3Client->getCommand('GetObject', $options);
            $request = $preSign ? $S3Client->createPresignedRequest($command, '+5Minutes') : \Aws\serialize($command);
            return (string)$request->getUri();
        }

        else if ($adapter instanceof GoogleStorageAdapter) {
            throw new InvalidArgumentException('Not implemented yet.');
        }

        else if ($adapter instanceof Local) {
            throw new InvalidArgumentException('Not implemented yet.');
        }

        else {
            throw new InvalidArgumentException(sprintf('Cannot handle flysystem adapter "%s".', get_class($adapter)));
        }
    }
}

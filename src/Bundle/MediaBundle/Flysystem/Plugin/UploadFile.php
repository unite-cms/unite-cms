<?php


namespace UniteCMS\MediaBundle\Flysystem\Plugin;

use InvalidArgumentException;
use League\Flysystem\Adapter\Local;
use League\Flysystem\AwsS3v3\AwsS3Adapter;
use League\Flysystem\FilesystemInterface;
use League\Flysystem\PluginInterface;
use Superbalist\Flysystem\GoogleStorage\GoogleStorageAdapter;
use UniteCMS\MediaBundle\Flysystem\UploadToken;

class UploadFile implements PluginInterface
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
        return 'createUploadToken';
    }

    /**
     * {@inheritDoc}
     */
    public function setFilesystem(FilesystemInterface $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    /**
     * Pre-sign a filename for upload using on of the defined adapter.
     *
     * @param string $filename
     * @param array $config
     *
     * @return UploadToken
     */
    public function handle(string $filename, array $config = []) : UploadToken
    {
        $uploadToken = new UploadToken();
        $uploadToken->setFilename($filename);

        $adapter = $this->filesystem->getAdapter();

        if($adapter instanceof AwsS3Adapter) {

            $options = [
                'Bucket' => $adapter->getBucket(),
                'Key' => $adapter->applyPathPrefix($config['tmp_path'] . '/' . $uploadToken->getId() . '/' . $filename),
            ];

            $S3Client = $adapter->getClient();
            $command = $S3Client->getCommand('PutObject', $options);
            $request = $S3Client->createPresignedRequest($command, '+5Minutes');

            $uploadToken
                ->setDriver('s3')
                ->setTmpUploadUrl((string) $request->getUri());
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

        return $uploadToken;
    }
}

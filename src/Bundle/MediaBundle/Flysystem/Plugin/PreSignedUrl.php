<?php


namespace UniteCMS\MediaBundle\Flysystem\Plugin;

use InvalidArgumentException;
use League\Flysystem\Adapter\Local;
use League\Flysystem\AwsS3v3\AwsS3Adapter;
use League\Flysystem\FilesystemInterface;
use League\Flysystem\PluginInterface;
use Superbalist\Flysystem\GoogleStorage\GoogleStorageAdapter;

class PreSignedUrl implements PluginInterface
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
        return 'getPreSignedUrl';
    }

    /**
     * {@inheritDoc}
     */
    public function setFilesystem(FilesystemInterface $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    /**
     * Presign a filname for upload using on of the defined adapter.
     *
     * @param string $filename
     * @return string
     */
    public function handle(string $filename) : string
    {
        $adapter = $this->filesystem->getAdapter();

        if($adapter instanceof AwsS3Adapter) {

            $options = [
                'Bucket' => $adapter->getBucket(),
                'Key' => $adapter->applyPathPrefix($filename),
            ];
            $S3Client = $adapter->getClient();
            $command = $S3Client->getCommand('getObject', $options);
            $request = $S3Client->createPresignedRequest($command, '+5Minutes');
            return (string) $request->getUri();
        }

        else if ($adapter instanceof GoogleStorageAdapter) {
            $uploader = $adapter->getStorageClient()->signedUrlUploader($filename, null);
            return $uploader->getResumeUri();
        }

        else if ($adapter instanceof Local) {
            throw new InvalidArgumentException('Not implemented yet.');
        }

        throw new InvalidArgumentException(sprintf('Cannot handle flysystem adapter "%s".', get_class($adapter)));
    }
}

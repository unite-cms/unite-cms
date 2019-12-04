<?php
/**
 * S3PresignedUrl plugin.
 */
namespace UniteCMS\MediaBundle\Flysystem\Plugin;

use League\Flysystem\FilesystemInterface;
use League\Flysystem\PluginInterface;

/**
 *  PresignedUrl plugin.
 */
class S3PresignedUrl implements PluginInterface
{
    /**
     * FilesystemInterface instance.
     *
     * @var FilesystemInterface
     */
    protected $filesystem;

    /**
     * Sets the Filesystem instance.
     *
     * @param FilesystemInterface $filesystem
     */
    public function setFilesystem(FilesystemInterface $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    /**
     * Gets the method name.
     *
     * @return string
     */
    public function getMethod()
    {
        return 'getPresignedUrl';
    }

    /**
     * Method logic.
     *
     * Get a Presigned Url for a file.
     *
     * @see https://docs.aws.amazon.com/aws-sdk-php/v3/guide/service/s3-presigned-url.html.
     *
     * @param   string  $path        File.
     * @param   string  $expiration  Expiration time of url.
     * @param   array  $getObjectOptions  Additional options for getObject command
     * @return  boolean              Presigned Url on success. False on failure.
     */
    public function handle($path, $expiration = "+20 minutes", $getObjectOptions = [])
    {
        $adapter = $this->filesystem->getAdapter();

        $options = [
            'Bucket' => $adapter->getBucket(),
            'Key' => $adapter->applyPathPrefix($path),
        ];
        $options = array_merge($options, $getObjectOptions);
        $S3Client = $adapter->getClient();
        $command = $S3Client->getCommand('getObject', $options /*+ $adapter->getOptions() */ );
        try {
            $request = $S3Client->createPresignedRequest($command, $expiration);
            return (string) $request->getUri();
        } catch (S3Exception $exception) {
            return false;
        }

        return false;
    }
}
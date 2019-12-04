<?php

/**
 * GooglePresignedUrl plugin.
 */
namespace UniteCMS\MediaBundle\Flysystem\Plugin;

use League\Flysystem\FilesystemInterface;
use League\Flysystem\PluginInterface;

class GooglePresignedUrl implements PluginInterface
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
     * Get a Presigned Url for a file
     *
     * @param   string  $path        File.
     * @param   string  $expiration  Expiration time of url.
     * @param   array  $getObjectOptions  Additional options for getObject command
     * @return  boolean              Presigned Url on success. False on failure.
     */
    public function handle($path, $expiration = "+20 minutes", $getObjectOptions = [])
    {
        /*
        $storageClient = new StorageClient([
            'projectId' => your gcp projectId here ,
            'keyFilePath' =>  your gcp keyFilePath here ,
        ]);
        $bucket = $storageClient->bucket($objPath);
        $object = $bucket->object();
        $url = $object->signedUrl(new \DateTime('+ ' . $duration . ' seconds'));
        return $url
        */

        /*$adapter = $this->filesystem->getAdapter();

        $options = [
            'Bucket' => $adapter->getBucket(),
            'Key' => $adapter->applyPathPrefix($path),
        ];
        $options = array_merge($options, $getObjectOptions);
        $S3Client = $adapter->getClient();
        $command = $S3Client->getCommand('getObject', $options );
        try {
            $request = $S3Client->createPresignedRequest($command, $expiration);
            return (string) $request->getUri();
        } catch (S3Exception $exception) {
            return false;
        }*/

        return false;
    }
}
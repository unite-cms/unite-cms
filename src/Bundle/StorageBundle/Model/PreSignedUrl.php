<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 13.02.18
 * Time: 10:01
 */

namespace UniteCMS\StorageBundle\Model;

/**
 * Holds all parts of an presigned url.
 *
 * @package UniteCMS\StorageBundle\Model
 */
class PreSignedUrl implements \JsonSerializable
{
    /**
     * The s3 preSigned URL including all auth parameters.
     *
     * @var string
     */
    private $preSignedUrl;

    /**
     * The uuid part of the URL.
     *
     * @var string
     */
    private $uuid;

    /**
     * The filename part of the URL.
     * @var string
     */
    private $filename;

    /**
     * A checksum hash for UUID and filename, to prevent manipulation, when
     * saving the uuid to the database.
     *
     * @var string
     */
    private $checksum;

    public function __construct(string $preSignedUrl, string $uuid, string $filename, string $checksum = null)
    {
        $this->preSignedUrl = $preSignedUrl;
        $this->uuid = $uuid;
        $this->filename = $filename;
        $this->checksum = $checksum;
    }

    /**
     * Sign this PreSignedURL with a custom secret. The computed hash gets
     * saved to $checksum.
     *
     * @param string $secret
     *
     * @return string
     */
    public function sign(string $secret)
    {
        $this->checksum = $this->computeHash($secret);

        return $this->checksum;
    }

    private function computeHash($secret)
    {
        return urlencode(base64_encode(hash_hmac('sha256', $this->uuid.'/'.$this->filename, $secret, true)));
    }

    /**
     * Check if the $checksum parameter is the correct checksum for the
     * provided uuid and filename.
     *
     * @param string $secret
     *
     * @return bool
     */
    public function check(string $secret)
    {
        return $this->checksum === $this->computeHash($secret);
    }

    /**
     * Specify data which should be serialized to JSON
     *
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
    public function jsonSerialize()
    {
        return [
            'pre_signed_url' => $this->getPreSignedUrl(),
            'uuid' => $this->getUuid(),
            'filename' => $this->getFilename(),
            'checksum' => $this->getChecksum(),
        ];
    }

    /**
     * @return string
     */
    public function getPreSignedUrl(): string
    {
        return $this->preSignedUrl;
    }

    /**
     * @return string
     */
    public function getUuid(): string
    {
        return $this->uuid;
    }

    /**
     * @return string
     */
    public function getFilename(): string
    {
        return $this->filename;
    }

    /**
     * @return string
     */
    public function getChecksum(): string
    {
        return $this->checksum;
    }
}

<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 31.07.18
 * Time: 17:55
 */

namespace UniteCMS\CoreBundle\Entity;

use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Type;

/**
 * @ExclusionPolicy("none")
 */
class FieldablePreview
{
    /**
     * @var string
     * @Assert\NotBlank(message="not_blank")
     * @Assert\Length(max="255", maxMessage="too_long")
     * @Assert\Url(protocols={"http", "https"}, message="invalid_url")
     * @Type("string")
     */
    private $url;

    /**
     * @var string
     * @Assert\NotBlank(message="not_blank")
     * TODO:@ValidGraphQlQuery(message="invalid_query")
     * @Type("string")
     */
    private $query;

    /**
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * @param string $url
     */
    public function setUrl(string $url): void
    {
        $this->url = $url;
    }

    /**
     * @return string
     */
    public function getQuery(): string
    {
        return $this->query;
    }

    /**
     * @param string $query
     */
    public function setQuery(string $query): void
    {
        $this->query = $query;
    }
}
<?php

namespace UniteCMS\CoreBundle\Entity;

use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Type;
use UniteCMS\CoreBundle\Validator\Constraints\ValidGraphQLQuery;
use UniteCMS\CoreBundle\Validator\Constraints\ValidWebhookCondition;

/**
 * @ExclusionPolicy("none")
 */
class Webhook
{

    /**
     * @var string
     * @Assert\NotBlank(message="not_blank")
     * @ValidGraphQLQuery(message="invalid_query")
     * @Type("string")
     */
    private $query;

    /**
     * @var string
     * @Assert\NotBlank(message="not_blank")
     * @Assert\Length(max="255", maxMessage="too_long")
     * @Assert\Url(protocols={"http", "https"}, message="invalid_url")
     * @Type("string")
     */
    private $url;

    /**
     * @var bool
     * @Type("bool")
     */
    private $check_ssl;

    /**
     * @var string
     * @Assert\Length(min="8", max="32", minMessage="too_short", maxMessage="too_long")
     * @Type("string")
     */
    private $authentication_header;

    /**
     * @var string
     * @Assert\NotBlank(message="not_blank")
     * @Assert\Length(min="4", max="255", minMessage="too_short", maxMessage="too_long")
     * @ValidWebhookCondition(message="invalid_expression")
     * @Type("string")
     */
    private $condition;

    public function __construct(string $query, string $url, string $condition, bool $check_ssl = true, string $authentication_header = null)
    {
        $this->query = $query;
        $this->url = $url;
        $this->check_ssl = $check_ssl;
        $this->authentication_header = $authentication_header;
        $this->condition = $condition;
    }

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

    /**
     * @return bool
     */
    public function getCheckSSL(): bool
    {
        return $this->check_ssl;
    }

    /**
     * @param bool $check_ssl
     */
    public function setCheckSSL(bool $check_ssl): void
    {
        $this->check_ssl = $check_ssl;
    }

    /**
     * @return string|null
     */
    public function getAuthenticationHeader(): ?string
    {
        return $this->authentication_header;
    }

    /**
     * @param string $authentication_header
     */
    public function setAuthenticationHeader(string $authentication_header): void
    {
        $this->authentication_header = $authentication_header;
    }

    /**
     * @return string
     */
    public function getCondition(): string
    {
        return $this->condition;
    }

    /**
     * @param string $condition
     */
    public function setCondition(string $condition): void
    {
        $this->condition = $condition;
    }
}
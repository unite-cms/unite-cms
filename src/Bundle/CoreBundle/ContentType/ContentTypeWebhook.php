<?php

namespace UniteCMS\CoreBundle\ContentType;

use Symfony\Component\Validator\Constraints as Assert;

class ContentTypeWebhook
{
    /**
     * @var string
     * @Assert\NotBlank
     */
    protected $expression;

    /**
     * @var string $url
     * @Assert\Url
     * @Assert\NotBlank
     */
    protected $url;

    /**
     * @var array $groups
     */
    protected $groups;

    public function __construct(string $expression, string $url, array $groups = [])
    {
        $this->expression = $expression;
        $this->url = $url;
        $this->groups = $groups;
    }

    /**
     * @return string
     */
    public function getExpression() : string {
        return $this->expression;
    }

    /**
     * @return string
     */
    public function getUrl() : string {
        return $this->url;
    }

    /**
     * @return array
     */
    public function getGroups() : array {
        return $this->groups;
    }
}

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

    public function __construct(string $expression, string $url)
    {
        $this->expression = $expression;
        $this->url = $url;
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
}

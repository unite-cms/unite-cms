<?php


namespace UniteCMS\CoreBundle\Content;


class ContentFilterInput
{
    static function fromInput(array $input) : self {
        return new self();
    }
}

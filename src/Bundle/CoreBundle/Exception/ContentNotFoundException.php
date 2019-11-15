<?php


namespace UniteCMS\CoreBundle\Exception;

use Exception;
use GraphQL\Error\ClientAware;

class ContentNotFoundException extends Exception implements ClientAware
{
    /**
     * {@inheritDoc}
     */
    public function isClientSafe()
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function getCategory()
    {
        return 'content';
    }
}

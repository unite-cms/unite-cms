<?php


namespace UniteCMS\CoreBundle\Exception;

use GraphQL\Error\ClientAware;
use Symfony\Component\Finder\Exception\AccessDeniedException;

class ContentAccessDeniedException extends AccessDeniedException implements ClientAware
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
        return 'access';
    }
}

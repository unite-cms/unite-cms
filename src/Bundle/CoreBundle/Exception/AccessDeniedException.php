<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 20.04.18
 * Time: 09:27
 */

namespace UniteCMS\CoreBundle\Exception;

use GraphQL\Error\ClientAware;

/**
 * This exception should be thrown when the current user is not allowed to access some property.
 *
 * Class InvalidFieldConfigurationException
 * @package UniteCMS\CoreBundle\Exception
 */
abstract class AccessDeniedException extends \Exception implements ClientAware
{

    /**
     * Returns true when exception message is safe to be displayed to a client.
     *
     * @api
     * @return bool
     */
    public function isClientSafe()
    {
        return true;
    }

    /**
     * Returns string describing a category of the error.
     *
     * Value "graphql" is reserved for errors produced by query parsing or validation, do not use it.
     *
     * @api
     * @return string
     */
    public function getCategory()
    {
        return 'field';
    }
}

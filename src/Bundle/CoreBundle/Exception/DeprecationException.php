<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 12.10.18
 * Time: 16:44
 */

namespace UniteCMS\CoreBundle\Exception;

/**
 * Throws an deprecation exception, that do not include any sensitive data and can be shown to the user.
 */
class DeprecationException extends \Error
{
    /**
     * @inheritdoc
     */
    public function __construct($message = "")
    {
        parent::__construct($message);
    }
}
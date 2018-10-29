<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 12.10.18
 * Time: 16:52
 */

namespace UniteCMS\CoreBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * An anonymous warning constraint that can be set to the execution context or can be extended to mark a constraint as
 * warning.
 */
class Warning extends Constraint
{
    public $payload = ['severity' => 'warning'];
}
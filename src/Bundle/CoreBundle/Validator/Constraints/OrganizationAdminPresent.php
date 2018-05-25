<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 25.05.18
 * Time: 12:11
 */

namespace UniteCMS\CoreBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class OrganizationAdminPresent extends Constraint
{
    public $message = 'There must be at least one administrator for every organization.';
}
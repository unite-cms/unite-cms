<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 14.06.18
 * Time: 08:51
 */

namespace UniteCMS\CoreBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraints\Regex;

/**
 * @Annotation
 */
class ValidIdentifier extends Regex
{
    /**
     * {@inheritdoc}
     */
    public function getRequiredOptions()
    {
        return array();
    }
}
<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 2018-11-30
 * Time: 13:31
 */

namespace UniteCMS\CoreBundle\Form;

use Symfony\Component\Form\AbstractType;

class FieldExceptionFormType extends AbstractType
{
    public function getBlockPrefix()
    {
        return 'unite_cms_core_field_exception';
    }
}
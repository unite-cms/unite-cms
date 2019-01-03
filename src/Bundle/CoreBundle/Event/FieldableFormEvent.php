<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 2019-01-03
 * Time: 10:11
 */

namespace UniteCMS\CoreBundle\Event;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormInterface;

class FieldableFormEvent extends FormEvent
{
    protected $fieldableData;

    public function __construct(FormInterface $form, $data, $fieldableData)
    {
        parent::__construct($form, $data);
        $this->fieldableData = $fieldableData;
    }

    /**
     * Returns the fieldable data associated with this event.
     *
     * @return mixed
     */
    public function getFieldableData()
    {
        return $this->fieldableData;
    }
}

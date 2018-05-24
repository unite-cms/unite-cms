<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 24.05.18
 * Time: 13:30
 */

namespace UniteCMS\CoreBundle\Event;


use Symfony\Component\EventDispatcher\Event;
use UniteCMS\CoreBundle\Form\Model\InvitationRegistrationModel;

class RegistrationEvent extends Event
{
    const REGISTRATION_SUCCESS = 'unite.registration.success';
    const REGISTRATION_COMPLETE = 'unite.registration.complete';
    const REGISTRATION_FAILURE = 'unite.registration.failure';

    /**
     * @var InvitationRegistrationModel $registrationModel
     */
    private $registrationModel;

    /**
     * @var string $context
     */
    private $context;

    public function __construct(InvitationRegistrationModel $registrationModel, string $context = 'invitation')
    {
        $this->registrationModel = $registrationModel;
        $this->context = $context;
    }

    /**
     * @return InvitationRegistrationModel
     */
    public function getRegistrationModel()
    {
        return $this->registrationModel;
    }

    /**
     * @return string
     */
    public function getContext(): string
    {
        return $this->context;
    }
}
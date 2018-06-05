<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 24.05.18
 * Time: 13:30
 */

namespace UniteCMS\CoreBundle\Event;

use Symfony\Component\EventDispatcher\Event;
use UniteCMS\CoreBundle\Entity\User;

class CancellationEvent extends Event
{
    const CANCELLATION_SUCCESS = 'unite.cancellation.success';
    const CANCELLATION_COMPLETE = 'unite.cancellation.complete';
    const CANCELLATION_FAILURE = 'unite.cancellation.failure';

    /**
     * @var User $user
     */
    private $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * @return User
     */
    public function getUser() : User
    {
        return $this->user;
    }
}
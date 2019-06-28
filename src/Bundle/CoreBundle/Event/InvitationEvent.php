<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 24.05.18
 * Time: 13:30
 */

namespace UniteCMS\CoreBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;
use UniteCMS\CoreBundle\Entity\Invitation;

class InvitationEvent extends Event
{
    const INVITATION_ACCEPTED = 'unite.invitation.accepted';
    const INVITATION_REJECTED = 'unite.invitation.rejected';

    /**
     * @var Invitation $invitation
     */
    private $invitation;

    public function __construct(Invitation $invitation)
    {
        $this->invitation = $invitation;
    }

    /**
     * @return Invitation
     */
    public function getInvitation()
    {
        return $this->invitation;
    }
}

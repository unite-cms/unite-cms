<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 07.09.18
 * Time: 11:58
 */

namespace UniteCMS\CoreBundle\EventSubscriber;

use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\TwigEngine;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Translation\TranslatorInterface;
use Twig\Error\Error;
use UniteCMS\CoreBundle\Entity\Invitation;
use UniteCMS\CoreBundle\Entity\Organization;
use UniteCMS\CoreBundle\Entity\OrganizationMember;
use UniteCMS\CoreBundle\Event\InvitationEvent;

class InvitationAdminNotifier implements EventSubscriberInterface
{
    /**
     * @var \Swift_Mailer $mailer
     */
    private $mailer;

    /**
     * @var LoggerInterface $logger
     */
    private $logger;

    /**
     * @var TwigEngine $template
     */
    private $template;

    /**
     * @var Translator $translator
     */
    private $translator;

    /**
     * @var string $mailer_sender
     */
    private $mailer_sender;

    public function __construct(\Swift_Mailer $mailer, TwigEngine $template, TranslatorInterface $translator, LoggerInterface $logger, string $mailer_sender)
    {
        $this->mailer = $mailer;
        $this->template = $template;
        $this->translator = $translator;
        $this->logger = $logger;
        $this->mailer_sender = $mailer_sender;
    }

    public static function getSubscribedEvents()
    {
        return [
            InvitationEvent::INVITATION_ACCEPTED => 'onAccept',
            InvitationEvent::INVITATION_REJECTED => 'onReject',
        ];
    }

    public function onAccept(InvitationEvent $event) {
        $this->sendEmail($event->getInvitation(), 'email.invitation.user_accepted');
    }

    public function onReject(InvitationEvent $event) {
        $this->sendEmail($event->getInvitation(), 'email.invitation.user_rejected');
    }

    /**
     * Sends out an notification email to the given user.
     *
     * @param Invitation $invitation
     * @param string $key
     */
    private function sendEmail(Invitation $invitation, string $key) {

        $to = $invitation->getOrganization()->getMembers()
            ->filter(function(OrganizationMember $member){
                return $member->getSingleRole() === Organization::ROLE_ADMINISTRATOR;
            })
            ->map(function(OrganizationMember $member) {
                return $member->getUser()->getEmail();
            })->toArray();

        $trans_params = [
            '%email%' => $invitation->getEmail(),
            '%organization%' => $invitation->getOrganization()->getTitle(),
        ];

        try {
            foreach($to as $email) {
                $message = (new \Swift_Message($this->translator->trans($key.'.subject', $trans_params)))
                    ->setFrom($this->mailer_sender)
                    ->setTo($email)
                    ->setBody(
                        $this->template->render('@UniteCMSCore/Emails/invitation-admin-notification.html.twig', [
                            'headline' => $this->translator->trans($key.'.subject', $trans_params),
                            'content' => $this->translator->trans($key.'.content', $trans_params),
                            'button' => $this->translator->trans($key.'.button', $trans_params),
                            'organization_identifier' => $invitation->getOrganization()->getIdentifier(),
                        ]),
                        'text/html'
                    );
                $this->mailer->send($message);
            }
        } catch (Error $e) {
            $this->logger->error('Error rendering email template.', ['exception' => $e]);
        } catch (\Swift_SwiftException $e) {
            $this->logger->error('Error sending email using swift mailer.', ['exception' => $e]);
        }
    }
}

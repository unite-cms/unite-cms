<?php


namespace UniteCMS\CoreBundle\Mailer;

use Swift_Message;

class InviteMailer extends BaseMailer
{
    /**
     * @param string $inviteToken
     * @param string $email
     * @param string|null $inviteText
     * @param string|null $inviteUrl
     *
     * @return int
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function send(string $inviteToken, string $email, ?string $inviteText = null, ?string $inviteUrl = null) : int {

        $message = new Swift_Message(
            $this->translator->trans('email.invite.subject'),
            $this->twig->render('@UniteCMSCore/email/invite.html.twig', [
                'inviteText' => $inviteText,
                'inviteUrl' => $this->defaultUrl($inviteUrl, '/email-confirm/invite/{token}'),
                'inviteToken' => $inviteToken,
            ]),
            'text/html'
        );

        $message
            ->setFrom($this->defaultMailerFrom)
            ->setTo($email);

        return $this->mailer->send($message);
    }
}

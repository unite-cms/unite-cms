<?php


namespace UniteCMS\CoreBundle\Mailer;

use Swift_Message;

class EmailChangeMailer extends BaseMailer
{
    /**
     * @param string $changToken
     * @param string $email
     * @param string|null $changeUrl
     *
     * @return int
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function send(string $changToken, string $email, ?string $changeUrl = null) : int {

        $message = new Swift_Message(
            $this->translator->trans('email.change_email.subject'),
            $this->twig->render('@UniteCMSCore/email/changeEmail.html.twig', [
                'changeUrl' => $this->defaultUrl($changeUrl, 'email-confirm/change-email/{token}'),
                'changeToken' => $changToken,
            ]),
            'text/html'
        );

        $message
            ->setFrom($this->defaultMailerFrom)
            ->setTo($email);

        return $this->mailer->send($message);
    }
}

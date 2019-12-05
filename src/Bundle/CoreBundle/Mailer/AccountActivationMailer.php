<?php


namespace UniteCMS\CoreBundle\Mailer;

use Swift_Message;

class AccountActivationMailer extends BaseMailer
{
    /**
     * @param string $activateToken
     * @param string $email
     * @param string|null $activateUrl
     *
     * @return int
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function send(string $activateToken, string $email, ?string $activateUrl = null) : int {

        $message = new Swift_Message(
            $this->translator->trans('email.account_activate.subject'),
            $this->twig->render('@UniteCMSCore/email/accountActivate.html.twig', [
                'activateUrl' => $this->defaultUrl($activateUrl, '/email-confirm/activate-account/{token}'),
                'activateToken' => $activateToken,
            ]),
            'text/html'
        );

        $message
            ->setFrom($this->defaultMailerFrom)
            ->setTo($email);

        return $this->mailer->send($message);
    }
}

<?php


namespace UniteCMS\CoreBundle\Mailer;

use Swift_Mailer;
use Swift_Message;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class PasswordResetMailer extends BaseMailer
{
    /**
     * @param string $resetToken
     * @param string $email
     * @param string|null $resetUrl
     *
     * @return int
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function send(string $resetToken, string $email, ?string $resetUrl = null) : int {

        $message = new Swift_Message(
            $this->translator->trans('email.password_reset.subject'),
            $this->twig->render('@UniteCMSCore/email/passwordReset.html.twig', [
                'resetUrl' => $this->defaultUrl($resetUrl, '/email-confirm/reset-password/{token}'),
                'resetToken' => $resetToken,
            ]),
            'text/html'
        );

        $message
            ->setFrom($this->defaultMailerFrom)
            ->setTo($email);

        return $this->mailer->send($message);
    }
}

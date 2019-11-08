<?php


namespace UniteCMS\CoreBundle\Mailer;

use Swift_Mailer;
use Swift_Message;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class EmailChangeMailer
{

    /**
     * @var Swift_Mailer $mailer
     */
    protected $mailer;

    /**
     * @var Environment $twig
     */
    protected $twig;

    /**
     * @var TranslatorInterface $translator
     */
    protected $translator;

    /**
     * @var $defaultMailerFrom
     */
    protected $defaultMailerFrom;

    public function __construct(Swift_Mailer $mailer, Environment $twig, TranslatorInterface $translator, string $defaultMailerFrom)
    {
        $this->mailer = $mailer;
        $this->twig = $twig;
        $this->translator = $translator;
        $this->defaultMailerFrom = $defaultMailerFrom;
    }

    /**
     * @param string $resetUrl
     * @param string $resetToken
     * @param string $email
     *
     * @return int
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function send(string $resetUrl, string $resetToken, string $email) : int {

        $message = new Swift_Message(
            $this->translator->trans('email.change_email.subject'),
            $this->twig->render('@UniteCMSCore/email/changeEmail.html.twig', [
                'changeUrl' => $resetUrl,
                'changeToken' => $resetToken,
            ]),
            'text/html'
        );

        $message
            ->setFrom($this->defaultMailerFrom)
            ->setTo($email);

        return $this->mailer->send($message);
    }
}

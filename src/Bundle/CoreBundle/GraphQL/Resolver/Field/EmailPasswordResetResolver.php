<?php


namespace UniteCMS\CoreBundle\GraphQL\Resolver\Field;

use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use UniteCMS\CoreBundle\Content\FieldDataMapper;
use UniteCMS\CoreBundle\Domain\DomainManager;
use UniteCMS\CoreBundle\Event\ContentEvent;
use UniteCMS\CoreBundle\Field\Types\EmailType;
use UniteCMS\CoreBundle\Field\Types\PasswordType;
use UniteCMS\CoreBundle\Mailer\PasswordResetMailer;

class EmailPasswordResetResolver extends AbstractEmailConfirmationResolver
{
    const TOKEN_KEY = 'unite_password_reset';
    const REQUEST_FIELD = 'emailPasswordResetRequest';
    const CONFIRM_FIELD = 'emailPasswordResetConfirm';

    /**
     * @var PasswordResetMailer $passwordResetMailer
     */
    protected $passwordResetMailer;

    public function __construct(DomainManager $domainManager, LoggerInterface $uniteCMSDomainLogger, ValidatorInterface $validator, FieldDataMapper $fieldDataMapper, JWTEncoderInterface $JWTEncoder, PasswordResetMailer $passwordResetMailer)
    {
        $this->passwordResetMailer = $passwordResetMailer;
        parent::__construct($domainManager, $uniteCMSDomainLogger, $validator, $fieldDataMapper, $JWTEncoder);
    }

    /**
     * @param string $type
     * @return array|null
     */
    protected function getDirectiveConfig(string $type) : ?array {

        $userType = $this->domainManager->current()->getContentTypeManager()->getUserType($type);

        if(!$userType) {
            return null;
        }

        $resetDirective = null;

        foreach ($userType->getDirectives() as $directive) {
            if($directive['name'] === 'emailPasswordReset') {
                $resetDirective = $directive['args'];
            }
        }

        if(empty($resetDirective)) {
            $this->uniteCMSDomainLogger->warning(sprintf('A user tried to request or confirm a password reset for the user type "%s", however this user type is not configured for password reset', $type));
            return null;
        }

        $emailField = $userType->getField($resetDirective['emailField']);
        $passwordField = $userType->getField($resetDirective['passwordField']);

        if(!$emailField) {
            $this->uniteCMSDomainLogger->warning(sprintf('Missing emailField "%s" for @emailPasswordReset of user type "%s"', $resetDirective['emailField'], $type));
            return null;
        }

        if(!$passwordField) {
            $this->uniteCMSDomainLogger->warning(sprintf('Missing passwordField "%s" for @emailPasswordReset of user type "%s"', $resetDirective['passwordField'], $type));
            return null;
        }

        if($emailField->getType() !== EmailType::getType()) {
            $this->uniteCMSDomainLogger->warning(sprintf('emailField "%s" for @emailPasswordReset of user type "%s" must be of type "%s".', $resetDirective['emailField'], $type, EmailType::getType()));
            return null;
        }

        if($passwordField->getType() !== PasswordType::getType()) {
            $this->uniteCMSDomainLogger->warning(sprintf('passwordField "%s" for @emailPasswordReset of user type "%s" must be of type "%s".', $resetDirective['passwordField'], $type, PasswordType::getType()));
            return null;
        }

        return $resetDirective;
    }

    /**
     * @param $args
     *
     * @return bool
     * @throws \Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTDecodeFailureException
     * @throws \Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTEncodeFailureException
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    protected function resolveRequest($args) : bool {

        if(!$config = $this->getDirectiveConfig($args['type'])) {
            return false;
        }

        $domain = $this->domainManager->current();
        $user = $domain->getUserManager()->findByUsername($domain, $args['type'], $args['username']);

        if(!$user) {
            $this->uniteCMSDomainLogger->warning(sprintf('A user tried to request a password reset for the unknown user "%s".', $args['username']));
            return false;
        }

        if(empty($email = $user->getFieldData($config['emailField']))) {
            $this->uniteCMSDomainLogger->warning(sprintf('User with username "%s" tried to request a password reset, however the value of field "%s" is empty.', $args['username'], $config['emailField']));
            return false;
        }

        if(!$this->isTokenEmptyOrExpired($user)) {
            $this->uniteCMSDomainLogger->warning(sprintf('User with username "%s" tried to request a password reset, however there already exist a non-expired reset token. If it is you: please wait until the token is expired and try again.', $args['username']));
            return false;
        }

        // Persist token
        $this->generateToken($user);
        $domain->getUserManager()->persist($domain, $user, ContentEvent::UPDATE);

        // Send out email
        if($this->passwordResetMailer->send($config['resetUrl'], $user->getToken(static::TOKEN_KEY), $email) === 0) {
            $this->uniteCMSDomainLogger->error(sprintf('Could not send out password reset email to user with username "%s".', $args['username']));
            return false;
        }

        // Replay to user
        $this->uniteCMSDomainLogger->info(sprintf('User with username "%s" requested a password reset.', $args['username']));
        return true;
    }

    /**
     * @param $args
     *
     * @return bool
     * @throws \Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTDecodeFailureException
     */
    protected function resolveConfirm($args) : bool {

        if(!$config = $this->getDirectiveConfig($args['type'])) {
            return false;
        }

        $domain = $this->domainManager->current();
        $user = $domain->getUserManager()->findByUsername($domain, $args['type'], $args['username']);

        if(!$user) {
            $this->uniteCMSDomainLogger->warning(sprintf('A user tried to confirm a password reset for the unknown user "%s".', $args['username']));
            return false;
        }

        if(!$this->isTokenValid($user, $args['token'])) {
            $this->uniteCMSDomainLogger->warning(sprintf('User with username "%s" tried to confirm a password reset, however the provided token is not valid.', $args['username']));
            return false;
        }

        // If we reach this point, we can save the new password.
        $this->tryToUpdate($user, $config['passwordField'], $args['password']);
        $user->setToken(static::TOKEN_KEY, null);
        $domain->getUserManager()->persist($domain, $user, ContentEvent::UPDATE);
        $this->uniteCMSDomainLogger->info(sprintf('Successfully confirmed password reset for user with username "%s".', $args['username']));
        return true;
    }
}

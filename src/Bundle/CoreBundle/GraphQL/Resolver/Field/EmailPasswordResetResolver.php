<?php


namespace UniteCMS\CoreBundle\GraphQL\Resolver\Field;

use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use UniteCMS\CoreBundle\Content\FieldDataMapper;
use UniteCMS\CoreBundle\Domain\DomainManager;
use UniteCMS\CoreBundle\Event\ContentEvent;
use UniteCMS\CoreBundle\Expression\SaveExpressionLanguage;
use UniteCMS\CoreBundle\Field\Types\EmailType;
use UniteCMS\CoreBundle\Field\Types\PasswordType;
use UniteCMS\CoreBundle\Log\LoggerInterface;
use UniteCMS\CoreBundle\Mailer\PasswordResetMailer;

class EmailPasswordResetResolver extends AbstractEmailConfirmationResolver
{
    const TOKEN_KEY = 'unite_password_reset';
    const REQUEST_FIELD = 'emailPasswordResetRequest';
    const CONFIRM_FIELD = 'emailPasswordResetConfirm';

    /**
     * @var SaveExpressionLanguage $expressionLanguage
     */
    protected $expressionLanguage;

    /**
     * @var PasswordResetMailer $passwordResetMailer
     */
    protected $passwordResetMailer;

    public function __construct(SaveExpressionLanguage $expressionLanguage, DomainManager $domainManager, ValidatorInterface $validator, FieldDataMapper $fieldDataMapper, JWTEncoderInterface $JWTEncoder, PasswordResetMailer $passwordResetMailer)
    {
        $this->expressionLanguage = $expressionLanguage;
        $this->passwordResetMailer = $passwordResetMailer;
        parent::__construct($domainManager, $validator, $fieldDataMapper, $JWTEncoder);
    }

    /**
     * @param string $type
     * @return array|null
     */
    protected function getDirectiveConfig(string $type) : ?array {

        $domain = $this->domainManager->current();
        $userType = $domain->getContentTypeManager()->getUserType($type);

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
            $domain->log(LoggerInterface::WARNING, sprintf('A user tried to request or confirm a password reset for the user type "%s", however this user type is not configured for password reset', $type));
            return null;
        }

        $emailField = $userType->getField($resetDirective['emailField']);
        $passwordField = $userType->getField($resetDirective['passwordField']);

        if(!$emailField) {
            $domain->log(LoggerInterface::WARNING, sprintf('Missing emailField "%s" for @emailPasswordReset of user type "%s"', $resetDirective['emailField'], $type));
            return null;
        }

        if(!$passwordField) {
            $domain->log(LoggerInterface::WARNING, sprintf('Missing passwordField "%s" for @emailPasswordReset of user type "%s"', $resetDirective['passwordField'], $type));
            return null;
        }

        if($emailField->getType() !== EmailType::getType()) {
            $domain->log(LoggerInterface::WARNING, sprintf('emailField "%s" for @emailPasswordReset of user type "%s" must be of type "%s".', $resetDirective['emailField'], $type, EmailType::getType()));
            return null;
        }

        if($passwordField->getType() !== PasswordType::getType()) {
            $domain->log(LoggerInterface::WARNING, sprintf('passwordField "%s" for @emailPasswordReset of user type "%s" must be of type "%s".', $resetDirective['passwordField'], $type, PasswordType::getType()));
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
            $domain->log(LoggerInterface::WARNING, sprintf('A user tried to request a password reset for the unknown user "%s".', $args['username']));
            return false;
        }

        if(empty($email = $user->getFieldData($config['emailField']))) {
            $domain->log(LoggerInterface::WARNING, sprintf('User with username "%s" tried to request a password reset, however the value of field "%s" is empty.', $args['username'], $config['emailField']));
            return false;
        }

        if(!empty($config['if']) && !$this->expressionLanguage->evaluate($config['if'], ['content' => $user]))  {
            $domain->log(LoggerInterface::WARNING, sprintf('User with username "%s" tried to request a password reset, however the directive if-expression evaluates to false.', $args['username']));
            return false;
        }

        if(!$this->isTokenEmptyOrExpired($user)) {
            $domain->log(LoggerInterface::WARNING, sprintf('User with username "%s" tried to request a password reset, however there already exist a non-expired reset token. If it is you: please wait until the token is expired and try again.', $args['username']));
            return false;
        }

        // Persist token
        $this->generateToken($user);
        $domain->getUserManager()->flush($domain);

        // Send out email
        if($this->passwordResetMailer->send($user->getToken(static::TOKEN_KEY), $email, $config['resetUrl'] ?? null) === 0) {
            $domain->log(LoggerInterface::ERROR, sprintf('Could not send out password reset email to user with username "%s".', $args['username']));
            return false;
        }

        // Replay to user
        $domain->log(LoggerInterface::NOTICE, sprintf('User with username "%s" requested a password reset.', $args['username']));
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
            $domain->log(LoggerInterface::WARNING, sprintf('A user tried to confirm a password reset for the unknown user "%s".', $args['username']));
            return false;
        }

        if(!empty($config['if']) && !$this->expressionLanguage->evaluate($config['if'], ['content' => $user]))  {
            $domain->log(LoggerInterface::WARNING, sprintf('User with username "%s" tried to request a password reset, however the directive if-expression evaluates to false.', $args['username']));
            return false;
        }

        if(!$this->isTokenValid($user, $args['token'])) {
            $domain->log(LoggerInterface::WARNING, sprintf('User with username "%s" tried to confirm a password reset, however the provided token is not valid.', $args['username']));
            return false;
        }

        // If we reach this point, we can save the new password.
        $this->tryToUpdate($user, $config['passwordField'], $args['password']);
        $user->setToken(static::TOKEN_KEY, null);
        $domain->getUserManager()->flush($domain);

        $domain->log(LoggerInterface::NOTICE, sprintf('Successfully confirmed password reset for user with username "%s".', $args['username']));
        return true;
    }
}

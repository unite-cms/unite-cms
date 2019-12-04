<?php


namespace UniteCMS\CoreBundle\GraphQL\Resolver\Field;

use GraphQL\Error\UserError;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use UniteCMS\CoreBundle\Content\FieldDataMapper;
use UniteCMS\CoreBundle\Domain\DomainManager;
use UniteCMS\CoreBundle\Expression\SaveExpressionLanguage;
use UniteCMS\CoreBundle\Field\Types\EmailType;
use UniteCMS\CoreBundle\Field\Types\PasswordType;
use UniteCMS\CoreBundle\Log\LoggerInterface;
use UniteCMS\CoreBundle\Mailer\InviteMailer;

class EmailInviteResolver extends AbstractEmailConfirmationResolver
{
    const TOKEN_KEY = 'unite_invite';
    const REQUEST_FIELD = 'emailInviteRequest';
    const CONFIRM_FIELD = 'emailInviteConfirm';

    /**
     * @var SaveExpressionLanguage $expressionLanguage
     */
    protected $expressionLanguage;

    /**
     * @var InviteMailer $inviteMailer
     */
    protected $inviteMailer;

    public function __construct(SaveExpressionLanguage $expressionLanguage, DomainManager $domainManager, ValidatorInterface $validator, FieldDataMapper $fieldDataMapper, JWTEncoderInterface $JWTEncoder, InviteMailer $inviteMailer)
    {
        $this->expressionLanguage = $expressionLanguage;
        $this->inviteMailer = $inviteMailer;
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

        $inviteDirective = null;

        foreach ($userType->getDirectives() as $directive) {
            if($directive['name'] === 'emailInvite') {
                $inviteDirective = $directive['args'];
            }
        }

        if(empty($inviteDirective)) {
            $domain->log(LoggerInterface::WARNING, sprintf('User type "%s" is not configured for email invitation.', $type));
            return null;
        }

        $emailField = $userType->getField($inviteDirective['emailField']);
        $passwordField = $userType->getField($inviteDirective['passwordField']);

        if(!$emailField) {
            $domain->log(LoggerInterface::WARNING, sprintf('Missing emailField "%s" for @emailPasswordReset of user type "%s"', $inviteDirective['emailField'], $type));
            return null;
        }

        if(!$passwordField) {
            $domain->log(LoggerInterface::WARNING, sprintf('Missing passwordField "%s" for @emailPasswordReset of user type "%s"', $inviteDirective['passwordField'], $type));
            return null;
        }

        if($emailField->getType() !== EmailType::getType()) {
            $domain->log(LoggerInterface::WARNING, sprintf('emailField "%s" for @emailPasswordReset of user type "%s" must be of type "%s".', $inviteDirective['emailField'], $type, EmailType::getType()));
            return null;
        }

        if($passwordField->getType() !== PasswordType::getType()) {
            $domain->log(LoggerInterface::WARNING, sprintf('passwordField "%s" for @emailPasswordReset of user type "%s" must be of type "%s".', $inviteDirective['passwordField'], $type, PasswordType::getType()));
            return null;
        }

        return $inviteDirective;
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
            $domain->log(LoggerInterface::WARNING, sprintf('Cannot invite user "%s", not user with this username was found.', $args['username']));
            return false;
        }

        if(!empty($config['if']) && !$this->expressionLanguage->evaluate($config['if'], ['content' => $user]))  {
            $domain->log(LoggerInterface::WARNING, sprintf('Cannot invite user "%s", because the directive if-expression evaluates to false.', $args['username']));
            return false;
        }

        if(empty($email = $user->getFieldData($config['emailField']))) {
            $domain->log(LoggerInterface::WARNING, sprintf('Cannot invite user "%s", because the value of field "%s" is empty.', $args['username'], $config['emailField']));
            return false;
        }

        if(!$user->getFieldData($config['passwordField'])->empty()) {
            $domain->log(LoggerInterface::WARNING, sprintf('Cannot invite user "%s", because a password is already present for this user.', $args['username']));
            return false;
        }

        if(!$this->isTokenEmptyOrExpired($user)) {
            $domain->log(LoggerInterface::WARNING, sprintf('Cannot invite user "%s", because there already exist a non-expired invitation token. If it is you: please wait until the token is expired and try again.', $args['username']));
            return false;
        }

        // Persist token
        $this->generateToken($user);
        $domain->getUserManager()->flush($domain);

        // Send out email
        if($this->inviteMailer->send($user->getToken(static::TOKEN_KEY), $email, $config['text'] ?? null, $config['inviteUrl'] ?? null) === 0) {
            $domain->log(LoggerInterface::ERROR, sprintf('Could not send out invitation email to user with username "%s".', $args['username']));
            return false;
        }

        // Replay to user
        $domain->log(LoggerInterface::NOTICE, sprintf('An invitation email was sent to user with username "%s.', $args['username']));
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
            $domain->log(LoggerInterface::WARNING, sprintf('Unknown user "%s" tries to confirm an invitation.', $args['username']));
            return false;
        }

        if(!empty($config['if']) && !$this->expressionLanguage->evaluate($config['if'], ['content' => $user]))  {
            $domain->log(LoggerInterface::WARNING, sprintf('Cannot invite user "%s", because the directive if-expression evaluates to false.', $args['username']));
            return false;
        }

        if(!$this->isTokenValid($user, $args['token'])) {
            $domain->log(LoggerInterface::WARNING, sprintf('User with username "%s" tried to confirm an invitation, however the provided token is not valid.', $args['username']));
            return false;
        }

        // If we reach this point, we can save the new password.
        $this->tryToUpdate($user, $config['passwordField'], $args['password']);
        $user->setToken(static::TOKEN_KEY, null);
        $domain->getUserManager()->flush($domain);

        $domain->log(LoggerInterface::NOTICE, sprintf('User "%s" successfully accepted the invitation.', $args['username']));
        return true;
    }
}

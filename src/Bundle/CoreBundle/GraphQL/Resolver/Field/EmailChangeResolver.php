<?php

namespace UniteCMS\CoreBundle\GraphQL\Resolver\Field;

use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use UniteCMS\CoreBundle\Content\FieldDataMapper;
use UniteCMS\CoreBundle\Domain\DomainManager;
use UniteCMS\CoreBundle\Expression\SaveExpressionLanguage;
use UniteCMS\CoreBundle\Field\Types\EmailType;
use UniteCMS\CoreBundle\Log\LoggerInterface;
use UniteCMS\CoreBundle\Mailer\EmailChangeMailer;
use UniteCMS\CoreBundle\Security\User\UserInterface;

class EmailChangeResolver extends AbstractEmailConfirmationResolver
{
    const TOKEN_KEY = 'unite_email_change';
    const REQUEST_FIELD = 'emailChangeRequest';
    const CONFIRM_FIELD = 'emailChangeConfirm';

    /**
     * @var SaveExpressionLanguage $expressionLanguage
     */
    protected $expressionLanguage;

    /**
     * @var Security $security
     */
    protected $security;

    /**
     * @var EmailChangeMailer $emailChangeMailer
     */
    protected $emailChangeMailer;

    public function __construct(
        SaveExpressionLanguage $expressionLanguage,
        Security $security,
        DomainManager $domainManager,
        ValidatorInterface $validator,
        FieldDataMapper $fieldDataMapper,
        JWTEncoderInterface $JWTEncoder,
        EmailChangeMailer $emailChangeMailer) {

        $this->expressionLanguage = $expressionLanguage;
        $this->security = $security;
        $this->emailChangeMailer = $emailChangeMailer;
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

        $changeDirective = null;

        foreach ($userType->getDirectives() as $directive) {
            if($directive['name'] === 'emailChange') {
                $changeDirective = $directive['args'];
            }
        }

        if(empty($changeDirective)) {
            $domain->log(LoggerInterface::WARNING, sprintf('A user tried to request or confirm an email change for the user type "%s", however this user type is not configured for email change.', $type));
            return null;
        }

        $emailField = $userType->getField($changeDirective['emailField']);

        if(!$emailField) {
            $domain->log(LoggerInterface::WARNING, sprintf('Missing emailField "%s" for @emailChange of user type "%s"', $changeDirective['emailField'], $type));
            return null;
        }

        if($emailField->getType() !== EmailType::getType()) {
            $domain->log(LoggerInterface::WARNING, sprintf('emailField "%s" for @emailChange of user type "%s" must be of type "%s".', $changeDirective['emailField'], $type, EmailType::getType()));
            return null;
        }

        return $changeDirective;
    }

    /**
     * @param $args
     *
     * @return bool
     * @throws \Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTEncodeFailureException
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     * @throws \Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTDecodeFailureException
     */
    protected function resolveRequest($args) : bool {

        $domain = $this->domainManager->current();
        $user = $this->security->getUser();

        if(!$user || !$user instanceof UserInterface) {
            $domain->log(LoggerInterface::WARNING, 'A user tried to request an email change for an anonymous user.');
            return false;
        }

        if(!$config = $this->getDirectiveConfig($user->getType())) {
            return false;
        }

        if($args['email'] === $user->getFieldData($config['emailField'])->resolveData()) {
            $domain->log(LoggerInterface::WARNING, 'User tried to request an email change for the same email.');
            return false;
        }

        if(!empty($config['if']) && !$this->expressionLanguage->evaluate($config['if'], ['content' => $user]))  {
            $domain->log(LoggerInterface::WARNING, sprintf('User with username "%s" tried to request an email chnage, however the directive if-expression evaluates to false.', $args['username']));
            return false;
        }

        if(!$this->isTokenEmptyOrExpired($user)) {
            $domain->log(LoggerInterface::WARNING, sprintf('User with username "%s" tried to request an email change, however there already exist a non-expired reset token. If it is you: please wait until the token is expired and try again.', $args['username']));
            return false;
        }

        // Validate user with this new email address.
        $this->tryToUpdate($user, $config['emailField'], $args['email'], true);

        // Generate activation token.
        $this->generateToken($user, ['email' => $args['email']]);
        $domain->getUserManager()->flush($domain);

        // Send out email
        if($this->emailChangeMailer->send($user->getToken(static::TOKEN_KEY), $args['email'], $config['changeUrl'] ?? null) === 0) {
            $domain->log(LoggerInterface::ERROR, 'Could not send out email change email.');
            return false;
        }

        // Replay to user
        $domain->log(LoggerInterface::NOTICE, 'User requested an email change.');
        return true;
    }

    /**
     * @param $args
     *
     * @return bool
     * @throws \Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTDecodeFailureException
     */
    protected function resolveConfirm($args) : bool {

        $domain = $this->domainManager->current();
        $user = $this->security->getUser();

        if(!$user || !$user instanceof UserInterface) {
            $domain->log(LoggerInterface::WARNING, 'A user tried to confirm an email change for an anonymous user.');
            return false;
        }

        if(!$config = $this->getDirectiveConfig($user->getType())) {
            return false;
        }

        // Check valid token.
        if(!$this->isTokenValid($user, $args['token'])) {
            return false;
        }

        $payload = $this->getTokenPayload($user);

        // If we reach this point, we can change the email
        $this->tryToUpdate($user, $config['emailField'], $payload['email']);
        $user->setToken(static::TOKEN_KEY, null);
        $domain->getUserManager()->flush($domain);
        $domain->log(LoggerInterface::NOTICE, 'Successfully confirmed email change.');
        return true;
    }
}

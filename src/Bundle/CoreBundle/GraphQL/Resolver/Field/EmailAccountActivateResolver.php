<?php


namespace UniteCMS\CoreBundle\GraphQL\Resolver\Field;

use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use UniteCMS\CoreBundle\Content\FieldDataMapper;
use UniteCMS\CoreBundle\Domain\DomainManager;
use UniteCMS\CoreBundle\Expression\SaveExpressionLanguage;
use UniteCMS\CoreBundle\Field\Types\EmailType;
use UniteCMS\CoreBundle\Log\LoggerInterface;
use UniteCMS\CoreBundle\Mailer\AccountActivationMailer;
use UniteCMS\CoreBundle\Security\User\UserInterface;

class EmailAccountActivateResolver extends AbstractEmailConfirmationResolver
{
    const TOKEN_KEY = 'unite_account_activate';
    const REQUEST_FIELD = 'emailAccountActivateRequest';
    const CONFIRM_FIELD = 'emailAccountActivateConfirm';

    /**
     * @var SaveExpressionLanguage $expressionLanguage
     */
    protected $expressionLanguage;

    /**
     * @var AccountActivationMailer $accountActivationMailer
     */
    protected $accountActivationMailer;

    public function __construct(
        SaveExpressionLanguage $expressionLanguage,
        DomainManager $domainManager,
        ValidatorInterface $validator,
        FieldDataMapper $fieldDataMapper,
        JWTEncoderInterface $JWTEncoder,
        AccountActivationMailer $accountActivationMailer) {

        $this->expressionLanguage = $expressionLanguage;
        $this->accountActivationMailer = $accountActivationMailer;
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

        $activateDirective = null;

        foreach ($userType->getDirectives() as $directive) {
            if($directive['name'] === 'emailAccountActivate') {
                $activateDirective = $directive['args'];
            }
        }

        if(empty($activateDirective)) {
            $domain->log(LoggerInterface::WARNING, sprintf('A user tried to request or confirm a password activation for the user type "%s", however this user type is not configured for account activation', $type));
            return null;
        }

        $emailField = $userType->getField($activateDirective['emailField']);
        $stateField = $userType->getField($activateDirective['stateField']);

        if(!$emailField) {
            $domain->log(LoggerInterface::WARNING, sprintf('Missing emailField "%s" for @emailAccountActivate of user type "%s"', $activateDirective['emailField'], $type));
            return null;
        }

        if(!$stateField) {
            $domain->log(LoggerInterface::WARNING, sprintf('Missing stateField "%s" for @emailAccountActivate of user type "%s"', $activateDirective['stateField'], $type));
            return null;
        }

        if($emailField->getType() !== EmailType::getType()) {
            $domain->log(LoggerInterface::WARNING, sprintf('emailField "%s" for @emailAccountActivate of user type "%s" must be of type "%s".', $activateDirective['emailField'], $type, EmailType::getType()));
            return null;
        }

        return $activateDirective;
    }

    /**
     * @param UserInterface $user
     * @param array $config
     *
     * @return bool
     */
    protected function canUserActivateAccount(UserInterface $user, array $config) : bool {
        $stateValue = $user->getFieldData($config['stateField'])->resolveData();
        if($stateValue == $config['activeValue']) {
            $this->domainManager->current()->log(LoggerInterface::WARNING, 'User tried to request an account activation, however the account is already activated.');
            return false;
        }
        return true;
    }

    /**
     * {@inheritDoc}
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
        $user = $domain->getUserManager()->findByUsername($domain, $args['username']);

        if(!$user) {
            $domain->log(LoggerInterface::WARNING, sprintf('A user tried to request an account activation for the unknown user "%s".', $args['username']));
            return false;
        }

        if(!$config = $this->getDirectiveConfig($user->getType())) {
            return false;
        }

        if(empty($email = $user->getFieldData($config['emailField']))) {
            $domain->log(LoggerInterface::WARNING, sprintf('User with username "%s" tried to request an account activation, however the value of field "%s" is empty.', $args['username'], $config['emailField']));
            return false;
        }

        if(!empty($config['if']) && !$this->expressionLanguage->evaluate($config['if'], ['content' => $user]))  {
            $domain->log(LoggerInterface::WARNING, sprintf('User with username "%s" tried to request an account activation, however the directive if-expression evaluates to false.', $args['username']));
            return false;
        }

        if(!$this->isTokenEmptyOrExpired($user)) {
            $domain->log(LoggerInterface::WARNING, sprintf('User with username "%s" tried to request a password reset, however there already exist a non-expired reset token. If it is you: please wait until the token is expired and try again.', $args['username']));
            return false;
        }

        // Check current state field value.
        if(!$this->canUserActivateAccount($user, $config)) {
            return false;
        }

        // Generate activation token.
        $this->generateToken($user);
        $domain->getUserManager()->flush($domain);

        // Send out email
        if($this->accountActivationMailer->send($user->getToken(static::TOKEN_KEY), $email, $config['activateUrl'] ?? null) === 0) {
            $domain->log(LoggerInterface::ERROR, sprintf('Could not send out account activation email to user with username "%s".', $args['username']));
            return false;
        }

        // Replay to user
        $domain->log(LoggerInterface::NOTICE, sprintf('User with username "%s" requested an account activation.', $args['username']));
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
        $user = $domain->getUserManager()->findByUsername($domain, $args['username']);

        if(!$user) {
            $domain->log(LoggerInterface::WARNING, sprintf('A user tried to confirm an account activation for the unknown user "%s".', $args['username']));
            return false;
        }

        if(!$config = $this->getDirectiveConfig($user->getType())) {
            return false;
        }

        // Check current state field value.
        if(!$this->canUserActivateAccount($user, $config)) {
            return false;
        }

        // If token is valid
        if(!$this->isTokenValid($user, $args['token'])) {
            return false;
        }

        // If we reach this point, we can activate the user.
        $this->tryToUpdate($user, $config['stateField'], $config['activeValue']);
        $user->setToken(static::TOKEN_KEY, null);
        $domain->getUserManager()->flush($domain);

        $domain->log(LoggerInterface::NOTICE, sprintf('Successfully confirmed account activation for user with username "%s".', $args['username']));
        return true;
    }
}

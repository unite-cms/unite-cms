<?php


namespace UniteCMS\CoreBundle\GraphQL\Resolver\Field;

use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use UniteCMS\CoreBundle\Content\FieldDataMapper;
use UniteCMS\CoreBundle\Domain\DomainManager;
use UniteCMS\CoreBundle\Event\ContentEvent;
use UniteCMS\CoreBundle\Field\Types\EmailType;
use UniteCMS\CoreBundle\Mailer\AccountActivationMailer;
use UniteCMS\CoreBundle\Security\User\UserInterface;

class EmailAccountActivateResolver extends AbstractEmailConfirmationResolver
{
    const TOKEN_KEY = 'unite_account_activate';
    const REQUEST_FIELD = 'emailAccountActivateRequest';
    const CONFIRM_FIELD = 'emailAccountActivateConfirm';

    /**
     * @var AccountActivationMailer $accountActivationMailer
     */
    protected $accountActivationMailer;

    public function __construct(
        DomainManager $domainManager,
        LoggerInterface $uniteCMSDomainLogger,
        ValidatorInterface $validator,
        FieldDataMapper $fieldDataMapper,
        JWTEncoderInterface $JWTEncoder,
        AccountActivationMailer $accountActivationMailer) {
        $this->accountActivationMailer = $accountActivationMailer;
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

        $activateDirective = null;

        foreach ($userType->getDirectives() as $directive) {
            if($directive['name'] === 'emailAccountActivate') {
                $activateDirective = $directive['args'];
            }
        }

        if(empty($activateDirective)) {
            $this->uniteCMSDomainLogger->warning(sprintf('A user tried to request or confirm a password activation for the user type "%s", however this user type is not configured for account activation', $type));
            return null;
        }

        $emailField = $userType->getField($activateDirective['emailField']);
        $stateField = $userType->getField($activateDirective['stateField']);

        if(!$emailField) {
            $this->uniteCMSDomainLogger->warning(sprintf('Missing emailField "%s" for @emailAccountActivate of user type "%s"', $activateDirective['emailField'], $type));
            return null;
        }

        if(!$stateField) {
            $this->uniteCMSDomainLogger->warning(sprintf('Missing stateField "%s" for @emailAccountActivate of user type "%s"', $activateDirective['stateField'], $type));
            return null;
        }

        if($emailField->getType() !== EmailType::getType()) {
            $this->uniteCMSDomainLogger->warning(sprintf('emailField "%s" for @emailAccountActivate of user type "%s" must be of type "%s".', $activateDirective['emailField'], $type, EmailType::getType()));
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
            $this->uniteCMSDomainLogger->warning(sprintf('User with username "%s" tried to request an account activation, however the account is already activated.', $user->getUsername()));
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

        if(!$config = $this->getDirectiveConfig($args['type'])) {
            return false;
        }

        $domain = $this->domainManager->current();
        $user = $domain->getUserManager()->findByUsername($domain, $args['type'], $args['username']);

        if(!$user) {
            $this->uniteCMSDomainLogger->warning(sprintf('A user tried to request an account activation for the unknown user "%s".', $args['username']));
            return false;
        }

        if(empty($email = $user->getFieldData($config['emailField']))) {
            $this->uniteCMSDomainLogger->warning(sprintf('User with username "%s" tried to request an account activation, however the value of field "%s" is empty.', $args['username'], $config['emailField']));
            return false;
        }

        if(!$this->isTokenEmptyOrExpired($user)) {
            $this->uniteCMSDomainLogger->warning(sprintf('User with username "%s" tried to request a password reset, however there already exist a non-expired reset token. If it is you: please wait until the token is expired and try again.', $args['username']));
            return false;
        }

        // Check current state field value.
        if(!$this->canUserActivateAccount($user, $config)) {
            return false;
        }

        // Generate activation token.
        $this->generateToken($user);
        $domain->getUserManager()->persist($domain, $user, ContentEvent::UPDATE);

        // Send out email
        if($this->accountActivationMailer->send($config['activateUrl'], $user->getToken(static::TOKEN_KEY), $email) === 0) {
            $this->uniteCMSDomainLogger->error(sprintf('Could not send out account activation email to user with username "%s".', $args['username']));
            return false;
        }

        // Replay to user
        $this->uniteCMSDomainLogger->info(sprintf('User with username "%s" requested an account activation.', $args['username']));
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
            $this->uniteCMSDomainLogger->warning(sprintf('A user tried to confirm an account activation for the unknown user "%s".', $args['username']));
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
        $domain->getUserManager()->persist($domain, $user, ContentEvent::UPDATE);
        $this->uniteCMSDomainLogger->info(sprintf('Successfully confirmed account activation for user with username "%s".', $args['username']));
        return true;
    }
}

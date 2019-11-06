<?php

namespace UniteCMS\CoreBundle\GraphQL\Resolver\Field;

use GraphQL\Language\AST\ObjectTypeDefinitionNode;
use GraphQL\Type\Definition\ResolveInfo;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Validator\Context\ExecutionContext;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\LocaleAwareInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Contracts\Translation\TranslatorTrait;
use UniteCMS\CoreBundle\Domain\DomainManager;
use UniteCMS\CoreBundle\Event\ContentEvent;
use UniteCMS\CoreBundle\Field\FieldTypeManager;
use UniteCMS\CoreBundle\Field\Types\EmailType;
use UniteCMS\CoreBundle\Mailer\AccountActivationMailer;
use UniteCMS\CoreBundle\Security\User\UserInterface;

class EmailAccountActivateResolver implements FieldResolverInterface
{
    /**
     * @var DomainManager $domainManager
     */
    protected $domainManager;

    /**
     * @var LoggerInterface $uniteCMSDomainLogger
     */
    protected $uniteCMSDomainLogger;

    /**
     * @var ValidatorInterface $validator
     */
    protected $validator;

    /**
     * @var FieldTypeManager $fieldTypeManager
     */
    protected $fieldTypeManager;

    /**
     * @var JWTEncoderInterface $JWTEncoder
     */
    protected $JWTEncoder;

    /**
     * @var AccountActivationMailer $accountActivationMailer
     */
    protected $accountActivationMailer;

    public function __construct(DomainManager $domainManager, LoggerInterface $uniteCMSDomainLogger, ValidatorInterface $validator, FieldTypeManager $fieldTypeManager, JWTEncoderInterface $JWTEncoder, AccountActivationMailer $accountActivationMailer)
    {
        $this->domainManager = $domainManager;
        $this->uniteCMSDomainLogger = $uniteCMSDomainLogger;
        $this->validator = $validator;
        $this->fieldTypeManager = $fieldTypeManager;
        $this->JWTEncoder = $JWTEncoder;
        $this->accountActivationMailer = $accountActivationMailer;
    }

    /**
     * {@inheritDoc}
     */
    public function supports(string $typeName, ObjectTypeDefinitionNode $typeDefinitionNode): bool {
        return $typeName === 'UniteMutation';
    }

    /**
     * {@inheritDoc}
     * @throws \Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTDecodeFailureException
     * @throws \Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTEncodeFailureException
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function resolve($value, $args, $context, ResolveInfo $info)
    {
        switch ($info->fieldName) {
            case 'emailAccountActivateRequest': return $this->resolveRequest($args);
            case 'emailAccountActivateConfirm': return $this->resolveConfirm($args);
            default: return null;
        }
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
     * @param string $token
     *
     * @param array $config
     *
     * @return bool
     * @throws \Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTDecodeFailureException
     */
    protected function isTokenValid(UserInterface $user, string $token, array $config) : bool {

        $payload = $this->JWTEncoder->decode($token);

        if($payload['exp'] < time()) {
            $this->uniteCMSDomainLogger->warning(sprintf('User with username "%s" tried to confirm an account activation, however the provided token is expired.', $user->getUsername()));
            return false;
        }

        if($payload['username'] !== $user->getUsername()) {
            $this->uniteCMSDomainLogger->warning(sprintf('User with username "%s" tried to confirm an account activation, however the provided token is not valid.', $user->getUsername()));
            return false;
        }

        $stateValue = $user->getFieldData($config['stateField'])->resolveData();
        if($payload['value'] !== sha1($stateValue)) {
            $this->uniteCMSDomainLogger->warning(sprintf('User with username "%s" tried to confirm an account activation, however the provided token is not valid.', $user->getUsername()));
            return false;
        }

        return true;
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
     * @param \UniteCMS\CoreBundle\Security\User\UserInterface $user
     * @param $config
     *
     * @return string
     *
     * @throws \Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTEncodeFailureException
     */
    protected function generateToken(UserInterface $user, $config) {
        $stateValue = $user->getFieldData($config['stateField'])->resolveData();
        return $this->JWTEncoder->encode([
            'value' => sha1($stateValue),
            'type' => 'email_account_activation',
            'username' => $user->getUsername(),
        ]);
    }

    /**
     * @param $args
     *
     * @return bool
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
            $this->uniteCMSDomainLogger->warning(sprintf('A user tried to request an account activation for the unknown user "%s".', $args['username']));
            return false;
        }

        if(empty($email = $user->getFieldData($config['emailField']))) {
            $this->uniteCMSDomainLogger->warning(sprintf('User with username "%s" tried to request an account activation, however the value of field "%s" is empty.', $args['username'], $config['emailField']));
            return false;
        }

        // Check current state field value.
        if(!$this->canUserActivateAccount($user, $config)) {
            return false;
        }

        // Generate activation token.
        $token = $this->generateToken($user, $config);

        // Send out email
        if($this->accountActivationMailer->send($config['activateUrl'], $token, $email) === 0) {
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

        // Generate activation token.
        if(!$this->isTokenValid($user, $args['token'], $config)) {
            return false;
        }


        // If we reach this point, we can activate the user.

        $stateField = $domain->getContentTypeManager()->getUserType($user->getType())->getField($config['stateField']);
        $stateFieldType = $this->fieldTypeManager->getFieldType($stateField->getType());

        // Update user state field
        $data = $user->getData();
        $data[$config['stateField']] = $stateFieldType->normalizeInputData($user, $stateField, $config['activeValue']);

        $domain->getUserManager()->update($domain, $user, $data);

        // Only validate the single state field.
        $context = new ExecutionContext($this->validator, $user, new class() implements TranslatorInterface, LocaleAwareInterface {
            use TranslatorTrait;
        });
        $stateFieldType->validateFieldData(
            $user,
            $stateField,
            $context->getValidator()->startContext()->atPath($config['stateField']),
            $context,
            $user->getFieldData($config['stateField'])
        );
        $violations = $context->getViolations();

        if(count($violations) > 0) {
            foreach($violations as $violation) {
                $this->uniteCMSDomainLogger->warning(sprintf('Could not confirm account activation for user with username "%s" because the password is not valid: %s', $args['username'], $violation->getMessage()));
            }
            return false;
        }

        // Persist new password
        $domain->getUserManager()->persist($domain, $user, ContentEvent::UPDATE);
        $this->uniteCMSDomainLogger->info(sprintf('Successfully confirmed account activation for user with username "%s".', $args['username']));
        return true;
    }
}

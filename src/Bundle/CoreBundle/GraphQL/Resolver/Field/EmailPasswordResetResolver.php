<?php

namespace UniteCMS\CoreBundle\GraphQL\Resolver\Field;

use GraphQL\Language\AST\ObjectTypeDefinitionNode;
use GraphQL\Type\Definition\ResolveInfo;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTDecodeFailureException;
use Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTEncodeFailureException;
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
use UniteCMS\CoreBundle\Field\Types\PasswordType;
use UniteCMS\CoreBundle\Mailer\PasswordResetMailer;
use UniteCMS\CoreBundle\Security\User\UserInterface;

class EmailPasswordResetResolver implements FieldResolverInterface
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
     * @var PasswordResetMailer $passwordResetMailer
     */
    protected $passwordResetMailer;

    public function __construct(DomainManager $domainManager, LoggerInterface $uniteCMSDomainLogger, ValidatorInterface $validator, FieldTypeManager $fieldTypeManager, JWTEncoderInterface $JWTEncoder, PasswordResetMailer $passwordResetMailer)
    {
        $this->domainManager = $domainManager;
        $this->uniteCMSDomainLogger = $uniteCMSDomainLogger;
        $this->validator = $validator;
        $this->fieldTypeManager = $fieldTypeManager;
        $this->JWTEncoder = $JWTEncoder;
        $this->passwordResetMailer = $passwordResetMailer;
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
            case 'emailPasswordResetRequest': return $this->resolveRequest($args);
            case 'emailPasswordResetConfirm': return $this->resolveConfirm($args);
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
     * @param UserInterface $user
     * @return bool
     *
     * @throws JWTDecodeFailureException
     */
    protected function isTokenEmptyOrExpired(UserInterface $user) : bool {

        $token = $user->getPasswordResetToken();

        if(empty($token)) {
            return true;
        }

        $payload = $this->JWTEncoder->decode($token);

        if($payload['exp'] < time()) {
            return true;
        }

        return false;
    }

    /**
     * @param UserInterface $user
     * @param string $token
     *
     * @return bool
     * @throws JWTDecodeFailureException
     */
    protected function isTokenValid(UserInterface $user, string $token) : bool {

        if($this->isTokenEmptyOrExpired($user)) {
            return false;
        }

        $storedToken = $user->getPasswordResetToken();
        $payload = $this->JWTEncoder->decode($storedToken);

        if($token !== $storedToken) {
            return false;
        }

        if($payload['username'] !== $user->getUsername()) {
            return false;
        }

        return true;
    }

    /**
     * @param UserInterface $user
     * @throws JWTEncodeFailureException
     */
    protected function generateToken(UserInterface $user) : void {
        $user->setPasswordResetToken(
            $this->JWTEncoder->encode([
                'username' => $user->getUsername(),
            ])
        );
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

        $this->generateToken($user);

        // Persist token
        $domain->getUserManager()->persist($domain, $user, ContentEvent::UPDATE);

        // Send out email
        if($this->passwordResetMailer->send($config['resetUrl'], $user->getPasswordResetToken(), $email) === 0) {
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

        if($this->isTokenEmptyOrExpired($user)) {
            $this->uniteCMSDomainLogger->warning(sprintf('User with username "%s" tried to confirm a password reset, however the provided token is expired.', $args['username']));
            return false;
        }

        if(!$this->isTokenValid($user, $args['token'])) {
            $this->uniteCMSDomainLogger->warning(sprintf('User with username "%s" tried to confirm a password reset, however the provided token is not valid.', $args['username']));
            return false;
        }

        // If we reach this point, we can save the new password.

        /**
         * @var PasswordType $passwordField
         */
        $passwordField = $this->fieldTypeManager->getFieldType(PasswordType::getType());

        // Update user password field.
        $data = $user->getData();
        $data[$config['passwordField']] = $passwordField->normalizePassword($user, $args['password']);
        $domain->getUserManager()->update($domain, $user, $data);

        // Only validate the single password field.
        $context = new ExecutionContext($this->validator, $user, new class() implements TranslatorInterface, LocaleAwareInterface {
            use TranslatorTrait;
        });
        $passwordField->validateFieldData(
            $user,
            $domain->getContentTypeManager()->getUserType($user->getType())->getField($config['passwordField']),
            $context->getValidator()->startContext()->atPath($config['passwordField']),
            $context,
            $user->getFieldData($config['passwordField'])
        );
        $violations = $context->getViolations();

        if(count($violations) > 0) {
            foreach($violations as $violation) {
                $this->uniteCMSDomainLogger->warning(sprintf('Could not confirm password reset for user with username "%s" because the password is not valid: %s', $args['username'], $violation->getMessage()));
            }
            return false;
        }

        // Clear token
        $user->setPasswordResetToken(null);

        // Persist new password
        $domain->getUserManager()->persist($domain, $user, ContentEvent::UPDATE);
        $this->uniteCMSDomainLogger->info(sprintf('Successfully confirmed password reset for user with username "%s".', $args['username']));
        return true;
    }
}

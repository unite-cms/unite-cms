<?php

namespace UniteCMS\CoreBundle\GraphQL\Resolver\Field;

use GraphQL\Language\AST\ObjectTypeDefinitionNode;
use GraphQL\Type\Definition\ResolveInfo;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use UniteCMS\CoreBundle\Domain\DomainManager;
use UniteCMS\CoreBundle\Event\ContentEvent;
use UniteCMS\CoreBundle\Exception\ConstraintViolationsException;
use UniteCMS\CoreBundle\Field\FieldTypeManager;
use UniteCMS\CoreBundle\Field\Types\EmailType;
use UniteCMS\CoreBundle\Mailer\EmailChangeMailer;
use UniteCMS\CoreBundle\Security\User\UserInterface;

class EmailChangeResolver implements FieldResolverInterface
{
    /**
     * @var Security $security
     */
    protected $security;

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
     * @var EmailChangeMailer $emailChangeMailer
     */
    protected $emailChangeMailer;

    public function __construct(Security $security, DomainManager $domainManager, LoggerInterface $uniteCMSDomainLogger, ValidatorInterface $validator, FieldTypeManager $fieldTypeManager, JWTEncoderInterface $JWTEncoder, EmailChangeMailer $emailChangeMailer)
    {
        $this->security = $security;
        $this->domainManager = $domainManager;
        $this->uniteCMSDomainLogger = $uniteCMSDomainLogger;
        $this->validator = $validator;
        $this->fieldTypeManager = $fieldTypeManager;
        $this->JWTEncoder = $JWTEncoder;
        $this->emailChangeMailer = $emailChangeMailer;
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
            case 'emailChangeRequest': return $this->resolveRequest($args);
            case 'emailChangeConfirm': return $this->resolveConfirm($args);
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

        $changeDirective = null;

        foreach ($userType->getDirectives() as $directive) {
            if($directive['name'] === 'emailChange') {
                $changeDirective = $directive['args'];
            }
        }

        if(empty($changeDirective)) {
            $this->uniteCMSDomainLogger->warning(sprintf('A user tried to request or confirm an email change for the user type "%s", however this user type is not configured for email change.', $type));
            return null;
        }

        $emailField = $userType->getField($changeDirective['emailField']);

        if(!$emailField) {
            $this->uniteCMSDomainLogger->warning(sprintf('Missing emailField "%s" for @emailChange of user type "%s"', $changeDirective['emailField'], $type));
            return null;
        }

        if($emailField->getType() !== EmailType::getType()) {
            $this->uniteCMSDomainLogger->warning(sprintf('emailField "%s" for @emailChange of user type "%s" must be of type "%s".', $changeDirective['emailField'], $type, EmailType::getType()));
            return null;
        }

        return $changeDirective;
    }

    /**
     * @param UserInterface $user
     * @param string $token
     *
     * @return array|null
     * @throws \Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTDecodeFailureException
     */
    protected function isTokenValid(UserInterface $user, string $token) : ?array {

        $payload = $this->JWTEncoder->decode($token);

        if($payload['exp'] < time()) {
            $this->uniteCMSDomainLogger->warning(sprintf('User with username "%s" tried to confirm an email change, however the provided token is expired.', $user->getUsername()));
            return null;
        }

        if($payload['username'] !== $user->getUsername()) {
            $this->uniteCMSDomainLogger->warning(sprintf('User with username "%s" tried to confirm an email change, however the provided token is not valid.', $user->getUsername()));
            return null;
        }

        return $payload;
    }

    /**
     * @param \UniteCMS\CoreBundle\Security\User\UserInterface $user
     * @param string $email
     *
     * @return string
     *
     * @throws \Lexik\Bundle\JWTAuthenticationBundle\Exception\JWTEncodeFailureException
     */
    protected function generateToken(UserInterface $user, string $email) {
        return $this->JWTEncoder->encode([
            'type' => 'email_change',
            'username' => $user->getUsername(),
            'email' => $email,
        ]);
    }

    /**
     * @param UserInterface $user
     * @param array $config
     * @param string $email
     * @param bool $resetValue
     */
    protected function tryToUpdateEmail(UserInterface $user, array $config, string $email, bool $resetValue = false) {

        $domain = $this->domainManager->current();
        $emailField = $domain->getContentTypeManager()->getUserType($user->getType())->getField($config['emailField']);
        $emailFieldType = $this->fieldTypeManager->getFieldType($emailField->getType());

        $data = $user->getData();
        $originalEmail = $data[$config['emailField']];
        $data[$config['emailField']] = $emailFieldType->normalizeInputData($user, $emailField, $email);

        $domain->getUserManager()->update($domain, $user, $data);
        $violations = $this->validator->validate($user);

        if($resetValue || count($violations) > 0) {
            $data[$config['emailField']] = $originalEmail;
            $domain->getUserManager()->update($domain, $user, $data);
        }

        if(count($violations) > 0) {
            throw new ConstraintViolationsException($violations);
        }
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

        $user = $this->security->getUser();

        if(!$user || !$user instanceof UserInterface) {
            $this->uniteCMSDomainLogger->warning('A user tried to request an email change for an anonymous user.');
            return false;
        }

        if(!$config = $this->getDirectiveConfig($user->getType())) {
            return false;
        }

        if($args['email'] === $user->getFieldData($config['emailField'])->resolveData()) {
            $this->uniteCMSDomainLogger->error(sprintf('User with username "%s" tried to request an email change for the same email.', $user->getUsername()));
            return false;
        }

        // Validate user with this new email address.
        $this->tryToUpdateEmail($user, $config, $args['email'], true);

        // Generate activation token.
        $token = $this->generateToken($user, $args['email']);

        // Send out email
        if($this->emailChangeMailer->send($config['changeUrl'], $token, $args['email']) === 0) {
            $this->uniteCMSDomainLogger->error(sprintf('Could not send out email change email to user with username "%s".', $user->getUsername()));
            return false;
        }

        // Replay to user
        $this->uniteCMSDomainLogger->info(sprintf('User with username "%s" requested an email change.', $user->getUsername()));
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
            $this->uniteCMSDomainLogger->warning('A user tried to confirm an email change for an anonymous user.');
            return false;
        }

        if(!$config = $this->getDirectiveConfig($user->getType())) {
            return false;
        }

        // Check valid token.
        if(($payload = $this->isTokenValid($user, $args['token'])) === null) {
            return false;
        }


        // If we reach this point, we can change the email
        $this->tryToUpdateEmail($user, $config, $payload['email']);
        $domain->getUserManager()->persist($domain, $user, ContentEvent::UPDATE);
        $this->uniteCMSDomainLogger->info(sprintf('Successfully confirmed email change for user with username "%s".', $user->getUsername()));
        return true;
    }
}

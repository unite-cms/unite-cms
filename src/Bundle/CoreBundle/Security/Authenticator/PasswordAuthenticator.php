<?php

namespace UniteCMS\CoreBundle\Security\Authenticator;

use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\ProviderNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;
use UniteCMS\CoreBundle\Content\ContentInterface;
use UniteCMS\CoreBundle\GraphQL\Schema\Provider\SchemaProviderInterface;
use UniteCMS\CoreBundle\GraphQL\SchemaManager;
use UniteCMS\CoreBundle\GraphQL\Util;
use UniteCMS\CoreBundle\Security\Encoder\FieldableUserPasswordEncoder;
use UniteCMS\CoreBundle\Security\Token\PreAuthenticationUniteUserToken;
use UniteCMS\CoreBundle\Security\User\TypeAwareUserProvider;

class PasswordAuthenticator extends AbstractGuardAuthenticator implements SchemaProviderInterface
{
    /**
     * @var FieldableUserPasswordEncoder $passwordEncoder
     */
    protected $passwordEncoder;

    /**
     * @var SchemaManager $schemaManager
     */
    protected $schemaManager;

    /**
     * @var LoggerInterface $uniteCMSDomainLogger
     */
    protected $domainLogger;

    public function __construct(FieldableUserPasswordEncoder $passwordEncoder, SchemaManager $schemaManager, LoggerInterface $uniteCMSDomainLogger)
    {
        $this->passwordEncoder = $passwordEncoder;
        $this->schemaManager = $schemaManager;
        $this->domainLogger = $uniteCMSDomainLogger;
    }

    /**
     * {@inheritDoc}
     */
    public function extend(): string
    {
        return file_get_contents(__DIR__ . '/../../Resources/GraphQL/Schema/Authenticator/password.graphql');
    }

    /**
     * {@inheritDoc}
     */
    public function start(Request $request, AuthenticationException $authException = null)
    {
        return new JsonResponse([
            'code' => 401,
            'message' => 'Auth header required',
        ], 401);
    }

    /**
     * Returns directive information if defined for this type.
     * @param string $userType
     * @return array|null
     */
    protected function getAuthDirective(string $userType) : array {

        $minimalSchema = $this->schemaManager->buildBaseSchema();

        if(!in_array($userType, array_keys($minimalSchema->getTypeMap()))) {
            $this->domainLogger->warning(sprintf('Unknown GraphQL type "%s" used for username/password login.', $userType));
            throw new ProviderNotFoundException(sprintf('The GraphQL type "%s" was not found.', $userType));
        }

        $userType = $minimalSchema->getType($userType);
        $directives = Util::getDirectives($userType->astNode);
        $passwordField = null;

        foreach ($directives as $directive) {
            if($directive['name'] === 'passwordAuthenticator') {
                return $directive;
            }
        }

        $this->domainLogger->warning(sprintf('@passwordAuthenticator was not configured for GraphQL user type "%s".', $userType));
        throw new ProviderNotFoundException(sprintf('Password authenticator is not enabled for user type "%s".', $userType));
    }

    /**
     * {@inheritDoc}
     */
    public function supports(Request $request)
    {
        return !empty($request->headers->get('PHP_AUTH_USER')) && !empty($request->headers->get('PHP_AUTH_PW')) && count(explode('/', $request->headers->get('PHP_AUTH_USER'))) === 2;
    }

    /**
     * {@inheritDoc}
     */
    public function getCredentials(Request $request)
    {
        $nameParts = explode('/', $request->headers->get('PHP_AUTH_USER'));
        return new PreAuthenticationUniteUserToken(
            $nameParts[1],
            $request->headers->get('PHP_AUTH_PW'),
            $nameParts[0],
            $this->getAuthDirective($nameParts[0])
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getUser($preAuthToken, UserProviderInterface $userProvider)
    {
        if (!$preAuthToken instanceof PreAuthenticationUniteUserToken) {
            throw new InvalidArgumentException(
                sprintf('The first argument of the "%s()" method must be an instance of "%s".', __METHOD__, PreAuthenticationUniteUserToken::class)
            );
        }

        if ($userProvider instanceof TypeAwareUserProvider) {
            $user = $userProvider->loadUserByUsernameAndType($preAuthToken->getUsername(), $preAuthToken->getType());

            if(!$user instanceof ContentInterface) {
                throw new InvalidArgumentException(sprintf('User must be an instance of "%s" in order to work with UsernamePasswordAuthenticator.', ContentInterface::class));
            }

            return $user;
        }

        return null;
    }

    /**
     * @param UserInterface | ContentInterface $user
     *
     * {@inheritDoc}
     */
    public function checkCredentials($preAuthToken, UserInterface $user)
    {
        if (!$preAuthToken instanceof PreAuthenticationUniteUserToken) {
            throw new InvalidArgumentException(
                sprintf('The first argument of the "%s()" method must be an instance of "%s".', __METHOD__, PreAuthenticationUniteUserToken::class)
            );
        }

        // Use the custom password field for checking password.
        if(empty($preAuthToken->getAuthDirective()['args']['passwordField'])) {
            return null;
        }

        return $this->passwordEncoder->isFieldPasswordValid(
            $user,
            $preAuthToken->getAuthDirective()['args']['passwordField'],
            $preAuthToken->getCredentials()
        );
    }

    /**
     * {@inheritDoc}
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception) {
        return new JsonResponse([
            'code' => 401,
            'message' => 'Username not found',
        ], 401);
    }

    /**
     * {@inheritDoc}
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey) {
        return;
    }

    /**
     * {@inheritDoc}
     */
    public function supportsRememberMe()
    {
        return false;
    }
}
<?php

namespace UniteCMS\CoreBundle\Security\Authenticator;

use InvalidArgumentException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;
use UniteCMS\CoreBundle\Security\Token\PreAuthenticationUniteUserToken;
use UniteCMS\CoreBundle\Security\User\TypeAwareUserProvider;

class UsernamePasswordAuthenticator extends AbstractGuardAuthenticator
{
    /**
     * @var UserPasswordEncoderInterface
     */
    protected $passwordEncoder;

    public function __construct(UserPasswordEncoderInterface $passwordEncoder)
    {
        $this->passwordEncoder = $passwordEncoder;
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
            $nameParts[0]
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
            return $userProvider->loadUserByUsernameAndType($preAuthToken->getUsername(), $preAuthToken->getType());
        }

        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function checkCredentials($preAuthToken, UserInterface $user)
    {
        if (!$preAuthToken instanceof PreAuthenticationUniteUserToken) {
            throw new InvalidArgumentException(
                sprintf('The first argument of the "%s()" method must be an instance of "%s".', __METHOD__, PreAuthenticationUniteUserToken::class)
            );
        }

        return $this->passwordEncoder->isPasswordValid($user, $preAuthToken->getCredentials());
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
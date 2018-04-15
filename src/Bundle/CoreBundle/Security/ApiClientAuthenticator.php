<?php

namespace UniteCMS\CoreBundle\Security;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;

class ApiClientAuthenticator extends AbstractGuardAuthenticator
{
    /**
     * Does the authenticator support the given Request?
     *
     * If this returns false, the authenticator will be skipped.
     *
     * @param Request $request
     *
     * @return bool
     */
    public function supports(Request $request)
    {
        return $request->headers->has('Authentication') || $request->query->has('token');
    }

    /**
     * Called on every request. Return credentials to be passed to getUser().
     * Returning null will cause this authenticator to be skipped.
     */
    public function getCredentials(Request $request)
    {
        // Try to get the token from authentication header.
        if ($authentication = $request->headers->get('Authentication')) {
            $prefix = 'Bearer ';
            if (substr($authentication, 0, strlen($prefix)) === $prefix) {
                return substr($authentication, strlen($prefix));
            }

            // If token do not start with Bearer, just return the whole string.
            return $authentication;
        }

        // Try to get the token from query get parameter.
        if ($request->query->has('token')) {
            return $request->query->get('token');
        }

        return null;
    }

    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        if (null === $credentials) {
            return;
        }

        // if a Api Client object, checkCredentials() is called
        return $userProvider->loadUserByUsername($credentials);
    }

    public function checkCredentials($credentials, UserInterface $user)
    {
        // We do not have a password for Api clients.
        return true;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        // on success, let the request continue
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        $data = array(
            'message' => strtr($exception->getMessageKey(), $exception->getMessageData())

            // or to translate this message
            // $this->translator->trans($exception->getMessageKey(), $exception->getMessageData())
        );

        return new JsonResponse($data, Response::HTTP_FORBIDDEN);
    }

    /**
     * Called when authentication is needed, but it's not sent
     */
    public function start(Request $request, AuthenticationException $authException = null)
    {
        $data = array(
            // you might translate this message
            'message' => 'Authentication Required'
        );

        return new JsonResponse($data, Response::HTTP_UNAUTHORIZED);
    }

    public function supportsRememberMe()
    {
        return false;
    }
}

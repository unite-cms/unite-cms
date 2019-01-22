<?php

namespace UniteCMS\CoreBundle\Security;

use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use UniteCMS\CoreBundle\Exception\MissingOrganizationException;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use UniteCMS\CoreBundle\Entity\ApiKey;
use UniteCMS\CoreBundle\Entity\Organization;

class ApiKeyUserProvider implements UserProviderInterface
{
    /**
     * @var RequestStack $requestStack
     */
    private $requestStack;

    /**
     * @var EntityManager $entityManager
     */
    private $entityManager;

    public function __construct(RequestStack $requestStack, EntityManager $entityManager)
    {
        $this->requestStack = $requestStack;
        $this->entityManager = $entityManager;
    }

    /**
     * Loads the user for the given username.
     *
     * This method must throw UsernameNotFoundException if the user is not
     * found.
     *
     * @param string $username The username
     *
     * @return UserInterface|ApiKey
     *
     * @throws UsernameNotFoundException if the token is not found
     * @throws MissingOrganizationException
     */
    public function loadUserByUsername($username)
    {
        $organization = $this->requestStack->getCurrentRequest()->attributes->get('organization');
        if(is_object($organization) && $organization instanceof Organization) {
            $organization = $organization->getIdentifier();
        }

        if(!is_string($organization) || empty($organization)) {
            throw new MissingOrganizationException('No organization was found in this request. The ApiKeyUserProvider can only operate under an organization scope.');
        }

        if (
            ($token = $this->entityManager
                ->getRepository('UniteCMSCoreBundle:ApiKey')
                ->findOneByTokenAndOrganization($username, $organization)
            )
        ) {
            return $token;
        }

        throw new UsernameNotFoundException("An API Key with token $username was not found for the current organization");
    }

    /**
     * Refreshes the user.
     *
     * It is up to the implementation to decide if the user data should be
     * totally reloaded (e.g. from the database), or if the UserInterface
     * object can just be merged into some internal array of users / identity
     * map.
     *
     * @param UserInterface $user
     *
     * @return UserInterface
     *
     * @throws UnsupportedUserException if the user is not supported
     */
    public function refreshUser(UserInterface $user)
    {
        if (!$user instanceof ApiKey) {
            throw new UnsupportedUserException('This provider ony supports API Key');
        }

        return $this->loadUserByUsername($user->getToken());
    }

    /**
     * Whether this provider supports the given user class.
     *
     * @param string $class
     *
     * @return bool
     */
    public function supportsClass($class)
    {
        return $class === ApiKey::class;
    }
}

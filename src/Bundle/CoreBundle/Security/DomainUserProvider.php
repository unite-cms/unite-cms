<?php


namespace UniteCMS\CoreBundle\Security;

use Lexik\Bundle\JWTAuthenticationBundle\Exception\InvalidPayloadException;
use Lexik\Bundle\JWTAuthenticationBundle\Security\User\PayloadAwareUserProviderInterface;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use UniteCMS\CoreBundle\Domain\DomainManager;

class DomainUserProvider implements PayloadAwareUserProviderInterface
{

    /**
     * @var DomainManager $domainManager
     */
    protected $domainManager;

    public function __construct(DomainManager $domainManager)
    {
        $this->domainManager = $domainManager;
    }

    /**
     * {@inheritdoc}
     */
    public function loadUserByUsername($username, array $payload = [])
    {
        return $this->loadUserByUsernameAndPayload($username, $payload);
    }

    /**
     * {@inheritDoc}
     */
    public function loadUserByUsernameAndPayload($username, array $payload)
    {
        if(empty($payload['roles'])) {
            throw new InvalidPayloadException('roles');
        }

        $type = substr($payload['roles'][0], strlen('ROLE_'));

        $domain = $this->domainManager->current();
        if(!$user = $domain->getUserManager()->findByUsername($domain, $type, $username)) {
            throw new UsernameNotFoundException();
        }

        return $user;
    }

    /**
     * {@inheritDoc}
     */
    public function refreshUser(UserInterface $user)
    {
        // TODO ?
    }

    /**
     * {@inheritDoc}
     */
    public function supportsClass($class)
    {
        return in_array(\UniteCMS\CoreBundle\User\UserInterface::class, class_implements($class));
    }
}

<?php


namespace UniteCMS\DoctrineORMBundle\User;

use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use UniteCMS\CoreBundle\Content\ContentInterface;
use UniteCMS\CoreBundle\Domain\Domain;
use UniteCMS\CoreBundle\User\UserInterface;
use UniteCMS\CoreBundle\User\UserManagerInterface;
use UniteCMS\DoctrineORMBundle\Content\ContentManager;
use UniteCMS\DoctrineORMBundle\Entity\User;

class UserManager extends ContentManager implements UserManagerInterface
{
    const ENTITY = User::class;

    /**
     * @var UserPasswordEncoderInterface $passwordEncoder
     */
    protected $passwordEncoder;

    public function __construct(RegistryInterface $registry, UserPasswordEncoderInterface $passwordEncoder)
    {
        parent::__construct($registry);
        $this->passwordEncoder = $passwordEncoder;
    }

    /**
     * {@inheritDoc}
     */
    public function findByUsername(Domain $domain, string $type, string $username): ?UserInterface {

        $user = $this->repository($domain)->typedFindByUsername($type, $username);

        // TODO: Remove mock creation
        if(!$user) {
            $user = new User($type, $username);

            // = "password"
            $user->setPassword('$argon2id$v=19$m=65536,t=4,p=1$5tYQxe/wtmO5FNJuztFUWw$GL6B8OL/ovotqeF80ZKZSmUIHS55Xyk/EKjmvBjQyhU');
        }

        return $user;
    }

    /**
     * {@inheritDoc}
     */
    public function create(Domain $domain, string $type, array $inputData = [], bool $persist = false): ContentInterface {

        $userNameField = $domain->getContentTypeManager()->getUserType($type)->getUserNameField();

        $user = new User($type, $inputData[$userNameField->getId()]);

        // TODO: Set password
        //$user->setPassword($this->passwordEncoder->encodePassword($user, $inputData[$userPasswordField->getId()]));

        unset($inputData[$userNameField->getId()]);

        $user->setData($inputData);

        if($persist) {
            $this->em($domain)->persist($user);

            // TODO Maybe we should not do this here, because of performance reasons.
            $this->em($domain)->flush($user);
        }

        return $user;
    }
}

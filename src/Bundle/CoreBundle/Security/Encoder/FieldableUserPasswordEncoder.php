<?php


namespace UniteCMS\CoreBundle\Security\Encoder;

use LogicException;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use UniteCMS\CoreBundle\Content\ContentInterface;

class FieldableUserPasswordEncoder implements UserPasswordEncoderInterface
{
    protected $encoderFactory;

    public function __construct(EncoderFactoryInterface $encoderFactory)
    {
        $this->encoderFactory = $encoderFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function encodePassword(UserInterface $user, $plainPassword, string $salt = '')
    {
        $encoder = $this->encoderFactory->getEncoder($user);
        return $encoder->encodePassword($plainPassword, $salt);
    }

    /**
     * {@inheritdoc}
     */
    public function isPasswordValid(UserInterface $user, $raw)
    {
        throw new LogicException('Please use method isFieldPasswordValid instead.');
    }

    /**
     * {@inheritdoc}
     */
    public function isFieldPasswordValid(ContentInterface $user, string $fieldName, $raw, string $salt = '')
    {
        $encoder = $this->encoderFactory->getEncoder($user);
        return $encoder->isPasswordValid($user->getFieldData($fieldName)->resolveData(), $raw, $salt);
    }

    /**
     * {@inheritdoc}
     */
    public function needsRehash(UserInterface $user): bool
    {
        if (null === $user->getPassword()) {
            return false;
        }

        $encoder = $this->encoderFactory->getEncoder($user);

        return $encoder->needsRehash($user->getPassword());
    }
}

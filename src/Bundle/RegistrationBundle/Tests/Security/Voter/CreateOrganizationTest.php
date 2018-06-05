<?php

namespace UniteCMS\RegistrationBundle\Tests\Functional;

use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use UniteCMS\CoreBundle\Entity\Organization;
use UniteCMS\CoreBundle\Entity\User;
use UniteCMS\CoreBundle\Security\Voter\OrganizationVoter;
use UniteCMS\CoreBundle\Tests\ContainerAwareTestCase;

class CreateOrganizationTest extends ContainerAwareTestCase {

    public function testUserIsAllowedToCreateAnOrganization() {

        // Anonymous users are not allowed to create organizations.
        $token = new AnonymousToken('foo', 'baa');
        self::$container->get('security.token_storage')->setToken($token);
        self::$container->get('session')->set('_security_main', serialize($token));
        $this->assertFalse(self::$container->get('security.authorization_checker')->isGranted(OrganizationVoter::CREATE, Organization::class));

        // Logged in users are allowed to create organizations, even if they are not an platform admin.
        $user = new User();
        $token = new UsernamePasswordToken($user, null, 'main', $user->getRoles());
        self::$container->get('security.token_storage')->setToken($token);
        self::$container->get('session')->set('_security_main', serialize($token));
        $this->assertTrue(self::$container->get('security.authorization_checker')->isGranted(OrganizationVoter::CREATE, Organization::class));
    }
}

<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 07.06.18
 * Time: 13:26
 */

namespace UniteCMS\RegistrationBundle\Tests\Subscriber;


use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use UniteCMS\CoreBundle\Entity\Organization;
use UniteCMS\CoreBundle\Entity\User;
use UniteCMS\CoreBundle\Tests\DatabaseAwareTestCase;

class CreateOrganizationSubscriberTest extends DatabaseAwareTestCase
{
    protected static function bootKernel(array $options = array())
    {
        $options['environment'] = 'test_registration';
        return parent::bootKernel($options);
    }

    /**
     * if no user is logged in, no membership can be created.
     */
    public function testMembershipCreationOnOrgCreationWithoutUser() {
        $org = new Organization();
        $org->setTitle('org')->setIdentifier('Org');
        $this->em->persist($org);
        $this->em->flush();
        $this->em->refresh($org);
        $this->assertCount(0, $org->getMembers());

        $token = new AnonymousToken('foo', 'baa');
        static::$container->get('security.token_storage')->setToken($token);
        static::$container->get('session')->set('_security_main', serialize($token));

        $org = new Organization();
        $org->setTitle('org2')->setIdentifier('Org2');
        $this->em->persist($org);
        $this->em->flush();
        $this->em->refresh($org);
        $this->assertCount(0, $org->getMembers());
    }

    /**
     * if a platform admin is logged in, no membership should be created.
     */
    public function testMembershipCreationOnOrgCreationForPlatformAdmin() {

        $user = new User();
        $user->setName('PAdmin')->setEmail('padmin@example.com')->setPassword('password')->setRoles([User::ROLE_PLATFORM_ADMIN]);
        $this->em->persist($user);
        $this->em->flush();

        $token = new UsernamePasswordToken($user, null, 'main', $user->getRoles());
        static::$container->get('security.token_storage')->setToken($token);
        static::$container->get('session')->set('_security_main', serialize($token));

        $org = new Organization();
        $org->setTitle('org')->setIdentifier('Org');
        $this->em->persist($org);
        $this->em->flush();
        $this->em->refresh($org);
        $this->assertCount(0, $org->getMembers());
    }

    /**
     * If a non-platform admin is logged in, this user should become org admin of the new organization.
     */
    public function testMembershipCreationOnOrgCreationForUser() {

        $user = new User();
        $user->setName('User')->setEmail('user@example.com')->setPassword('password')->setRoles([User::ROLE_USER]);
        $this->em->persist($user);
        $this->em->flush();

        $token = new UsernamePasswordToken($user, null, 'main', $user->getRoles());
        static::$container->get('security.token_storage')->setToken($token);
        static::$container->get('session')->set('_security_main', serialize($token));

        $org = new Organization();
        $org->setTitle('org')->setIdentifier('Org');
        $this->em->persist($org);
        $this->em->flush();
        $this->em->refresh($org);
        $this->assertCount(1, $org->getMembers());
        $this->assertEquals(Organization::ROLE_ADMINISTRATOR, $org->getMembers()->first()->getSingleRole());
        $this->assertEquals($user, $org->getMembers()->first()->getUser());
    }
}
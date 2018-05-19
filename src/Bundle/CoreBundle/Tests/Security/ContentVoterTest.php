<?php

namespace src\UniteCMS\CoreBundle\Tests\Security;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use UniteCMS\CoreBundle\Entity\Content;
use UniteCMS\CoreBundle\Entity\ContentType;
use UniteCMS\CoreBundle\Entity\Domain;
use UniteCMS\CoreBundle\Entity\DomainMember;
use UniteCMS\CoreBundle\Entity\Organization;
use UniteCMS\CoreBundle\Entity\OrganizationMember;
use UniteCMS\CoreBundle\Entity\User;
use UniteCMS\CoreBundle\Security\Voter\ContentVoter;
use UniteCMS\CoreBundle\Security\DeletedContentVoter;
use UniteCMS\CoreBundle\Tests\SecurityVoterTestCase;

class ContentVoterTest extends SecurityVoterTestCase
{

    /**
     * @var Domain
     */
    protected $domain1;

    /**
     * @var Domain
     */
    protected $domain2;

    /**
     * @var Content
     */
    protected $content1;

    /**
     * @var Content
     */
    protected $content2;

    /**
     * @var ContentType
     */
    protected $contentType1;

    /**
     * @var ContentType
     */
    protected $contentType2;

    public function setUp()
    {
        parent::setUp();

        $this->domain1 = new Domain();
        $this->domain1->setOrganization($this->org1)->setId(1);

        $this->domain2 = new Domain();
        $this->domain2->setOrganization($this->org2)->setId(2);

        $this->contentType1 = new ContentType();
        $this->contentType1->setDomain($this->domain1);
        $p1 = $this->contentType1->getPermissions();
        $p1[ContentVoter::UPDATE] = 'member.type == "editor" || member.type == "viewer"';
        $this->contentType1->setPermissions($p1);

        $this->contentType2 = new ContentType();
        $this->contentType2->setDomain($this->domain2);

        $this->content1 = new Content();
        $this->content1->setContentType($this->contentType1);

        $this->content2 = new Content();
        $this->content2->setContentType($this->contentType2);

        $admin = new User();
        $admin->setRoles([User::ROLE_USER])->setName('Admin');
        $adminMember = new OrganizationMember();
        $adminMember->setRoles([Organization::ROLE_USER])->setOrganization($this->org2);
        $adminDomainMember = new DomainMember();
        $adminDomainMember->setDomain($this->domain1)->setDomainMemberType($this->domain1->getDomainMemberTypes()->get('editor'));
        $admin->addOrganization($adminMember);
        $admin->addDomain($adminDomainMember);
        $this->u['domain_admin'] = new UsernamePasswordToken($admin, 'password', 'main', $admin->getRoles());

        $user = new User();
        $user->setRoles([User::ROLE_USER]);
        $userMember = new OrganizationMember();
        $userMember->setRoles([Organization::ROLE_USER])->setOrganization($this->org2);
        $userDomainMember = new DomainMember();
        $userDomainMember->setDomain($this->domain1)->setDomainMemberType($this->domain1->getDomainMemberTypes()->get('viewer'));
        $user->addOrganization($userMember);
        $user->addDomain($userDomainMember);
        $this->u['domain_editor'] = new UsernamePasswordToken($user, 'password', 'main', $user->getRoles());
    }

    public function testFallbackForNotSupportedArguments()
    {
        $voter = new ContentVoter();

        $invalidUser = new class
        {
            public function __toString()
            {
                return 'any';
            }
        };

        // Try with invalid token.
        $this->assertNotEquals(VoterInterface::ACCESS_GRANTED, $voter->vote(
            new UsernamePasswordToken($invalidUser, '', 'main', []),
            $this->contentType1,
            [ContentVoter::VIEW]
        ));

        // Try with invalid subject.
        $this->assertNotEquals(VoterInterface::ACCESS_GRANTED, $voter->vote(
            $this->u['domain_admin'],
            (object)[],
            [ContentVoter::VIEW]
        ));

        // Try with invalid attribute.
        $this->assertNotEquals(VoterInterface::ACCESS_GRANTED, $voter->vote(
            $this->u['domain_admin'],
            $this->contentType1,
            ['any']
        ));
    }

    public function testCRUDActions()
    {

        $dm = $this->container->get('security.authorization_checker');

        // Platform admins can preform all content actions.
        $this->container->get('security.token_storage')->setToken($this->u['platform']);
        $this->assertTrue($dm->isGranted([ContentVoter::LIST], $this->contentType1));
        $this->assertTrue($dm->isGranted([ContentVoter::CREATE], $this->contentType1));
        $this->assertTrue($dm->isGranted([ContentVoter::VIEW], $this->content1));
        $this->assertTrue($dm->isGranted([ContentVoter::UPDATE], $this->content1));
        $this->assertTrue($dm->isGranted([ContentVoter::DELETE], $this->content1));

        // Organization admins can preform all content actions on their organization domain's content.
        $this->container->get('security.token_storage')->setToken($this->u['admin']);
        $this->assertTrue($dm->isGranted([ContentVoter::LIST], $this->contentType2));
        $this->assertTrue($dm->isGranted([ContentVoter::CREATE], $this->contentType2));
        $this->assertTrue($dm->isGranted([ContentVoter::VIEW], $this->content2));
        $this->assertTrue($dm->isGranted([ContentVoter::UPDATE], $this->content2));
        $this->assertTrue($dm->isGranted([ContentVoter::DELETE], $this->content2));

        $this->assertFalse($dm->isGranted([ContentVoter::LIST], $this->contentType1));
        $this->assertFalse($dm->isGranted([ContentVoter::CREATE], $this->contentType1));
        $this->assertFalse($dm->isGranted([ContentVoter::VIEW], $this->content1));
        $this->assertFalse($dm->isGranted([ContentVoter::UPDATE], $this->content1));
        $this->assertFalse($dm->isGranted([ContentVoter::DELETE], $this->content1));

        // All other users can preform the actions they have access to.
        $this->container->get('security.token_storage')->setToken($this->u['domain_admin']);
        $this->assertTrue($dm->isGranted([ContentVoter::LIST], $this->contentType1));
        $this->assertTrue($dm->isGranted([ContentVoter::CREATE], $this->contentType1));
        $this->assertTrue($dm->isGranted([ContentVoter::VIEW], $this->content1));
        $this->assertTrue($dm->isGranted([ContentVoter::UPDATE], $this->content1));
        $this->assertTrue($dm->isGranted([ContentVoter::DELETE], $this->content1));

        $this->assertFalse($dm->isGranted([ContentVoter::LIST], $this->contentType2));
        $this->assertFalse($dm->isGranted([ContentVoter::CREATE], $this->contentType2));
        $this->assertFalse($dm->isGranted([ContentVoter::VIEW], $this->content2));
        $this->assertFalse($dm->isGranted([ContentVoter::UPDATE], $this->content2));
        $this->assertFalse($dm->isGranted([ContentVoter::DELETE], $this->content2));

        $this->container->get('security.token_storage')->setToken($this->u['domain_editor']);
        $this->assertTrue($dm->isGranted([ContentVoter::LIST], $this->contentType1));
        $this->assertFalse($dm->isGranted([ContentVoter::CREATE], $this->contentType1));
        $this->assertTrue($dm->isGranted([ContentVoter::VIEW], $this->content1));
        $this->assertTrue($dm->isGranted([ContentVoter::UPDATE], $this->content1));
        $this->assertFalse($dm->isGranted([ContentVoter::DELETE], $this->content1));

        $this->assertFalse($dm->isGranted([ContentVoter::LIST], $this->contentType2));
        $this->assertFalse($dm->isGranted([ContentVoter::CREATE], $this->contentType2));
        $this->assertFalse($dm->isGranted([ContentVoter::VIEW], $this->content2));
        $this->assertFalse($dm->isGranted([ContentVoter::UPDATE], $this->content2));
        $this->assertFalse($dm->isGranted([ContentVoter::DELETE], $this->content2));

        // Anonymous user have only access to content if it is granted
        $this->container->get('security.token_storage')->setToken($this->u['anonymous']);
        $this->assertFalse($dm->isGranted([ContentVoter::LIST], $this->contentType2));
        $this->assertFalse($dm->isGranted([ContentVoter::CREATE], $this->contentType2));
        $this->assertFalse($dm->isGranted([ContentVoter::VIEW], $this->content2));
        $this->assertFalse($dm->isGranted([ContentVoter::UPDATE], $this->content2));
        $this->assertFalse($dm->isGranted([ContentVoter::DELETE], $this->content2));
    }

    public function testCRUDActionsForDeletedContent()
    {

        $dm = $this->container->get('security.authorization_checker');

        $reflector = new \ReflectionProperty(Content::class, 'deleted');
        $reflector->setAccessible(true);
        $reflector->setValue($this->content1, new \DateTime());
        $reflector->setValue($this->content2, new \DateTime());

        // Platform admins can preform all content actions.
        $this->container->get('security.token_storage')->setToken($this->u['platform']);
        $this->assertTrue($dm->isGranted([ContentVoter::LIST], $this->contentType1));
        $this->assertTrue($dm->isGranted([ContentVoter::CREATE], $this->contentType1));
        $this->assertTrue($dm->isGranted([ContentVoter::VIEW], $this->content1));
        $this->assertTrue($dm->isGranted([ContentVoter::UPDATE], $this->content1));
        $this->assertTrue($dm->isGranted([ContentVoter::DELETE], $this->content1));

        // Organization admins can preform all content actions on their organization domain's content.
        $this->container->get('security.token_storage')->setToken($this->u['admin']);
        $this->assertTrue($dm->isGranted([ContentVoter::LIST], $this->contentType2));
        $this->assertTrue($dm->isGranted([ContentVoter::CREATE], $this->contentType2));
        $this->assertTrue($dm->isGranted([ContentVoter::VIEW], $this->content2));
        $this->assertTrue($dm->isGranted([ContentVoter::UPDATE], $this->content2));
        $this->assertTrue($dm->isGranted([ContentVoter::DELETE], $this->content2));

        $this->assertFalse($dm->isGranted([ContentVoter::LIST], $this->contentType1));
        $this->assertFalse($dm->isGranted([ContentVoter::CREATE], $this->contentType1));
        $this->assertFalse($dm->isGranted([ContentVoter::VIEW], $this->content1));
        $this->assertFalse($dm->isGranted([ContentVoter::UPDATE], $this->content1));
        $this->assertFalse($dm->isGranted([ContentVoter::DELETE], $this->content1));

        // All other users can preform the actions they have access to.
        $this->container->get('security.token_storage')->setToken($this->u['domain_admin']);
        $this->assertTrue($dm->isGranted([ContentVoter::LIST], $this->contentType1));
        $this->assertTrue($dm->isGranted([ContentVoter::CREATE], $this->contentType1));
        $this->assertTrue($dm->isGranted([ContentVoter::VIEW], $this->content1));
        $this->assertTrue($dm->isGranted([ContentVoter::UPDATE], $this->content1));
        $this->assertTrue($dm->isGranted([ContentVoter::DELETE], $this->content1));

        $this->assertFalse($dm->isGranted([ContentVoter::LIST], $this->contentType2));
        $this->assertFalse($dm->isGranted([ContentVoter::CREATE], $this->contentType2));
        $this->assertFalse($dm->isGranted([ContentVoter::VIEW], $this->content2));
        $this->assertFalse($dm->isGranted([ContentVoter::UPDATE], $this->content2));
        $this->assertFalse($dm->isGranted([ContentVoter::DELETE], $this->content2));

        $this->container->get('security.token_storage')->setToken($this->u['domain_editor']);
        $this->assertTrue($dm->isGranted([ContentVoter::LIST], $this->contentType1));
        $this->assertFalse($dm->isGranted([ContentVoter::CREATE], $this->contentType1));
        $this->assertTrue($dm->isGranted([ContentVoter::VIEW], $this->content1));
        $this->assertTrue($dm->isGranted([ContentVoter::UPDATE], $this->content1));
        $this->assertTrue($dm->isGranted([ContentVoter::DELETE], $this->content1));

        $this->assertFalse($dm->isGranted([ContentVoter::LIST], $this->contentType2));
        $this->assertFalse($dm->isGranted([ContentVoter::CREATE], $this->contentType2));
        $this->assertFalse($dm->isGranted([ContentVoter::VIEW], $this->content2));
        $this->assertFalse($dm->isGranted([ContentVoter::UPDATE], $this->content2));
        $this->assertFalse($dm->isGranted([ContentVoter::DELETE], $this->content2));

        // Anonymous user have only access to content if it is granted
        $this->container->get('security.token_storage')->setToken($this->u['anonymous']);
        $this->assertFalse($dm->isGranted([ContentVoter::LIST], $this->contentType2));
        $this->assertFalse($dm->isGranted([ContentVoter::CREATE], $this->contentType2));
        $this->assertFalse($dm->isGranted([ContentVoter::VIEW], $this->content2));
        $this->assertFalse($dm->isGranted([ContentVoter::UPDATE], $this->content2));
        $this->assertFalse($dm->isGranted([ContentVoter::DELETE], $this->content2));
    }
}

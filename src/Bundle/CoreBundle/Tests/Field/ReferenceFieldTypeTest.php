<?php

namespace UniteCMS\CoreBundle\Tests\Field;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use UniteCMS\CoreBundle\Entity\ApiKey;
use UniteCMS\CoreBundle\Entity\ContentType;
use UniteCMS\CoreBundle\Entity\Domain;
use UniteCMS\CoreBundle\Entity\DomainMember;
use UniteCMS\CoreBundle\Entity\User;
use UniteCMS\CoreBundle\Entity\View;
use UniteCMS\CoreBundle\Field\FieldableFieldSettings;
use UniteCMS\CoreBundle\Security\Voter\ContentVoter;
use UniteCMS\CoreBundle\Service\UniteCMSManager;
use UniteCMS\CoreBundle\View\ViewParameterBag;

class ReferenceFieldTypeTest extends FieldTypeTestCase
{
    public function testContentTypeFieldTypeWithEmptySettings() {

        // Content Type Field with empty settings should not be valid.
        $ctField = $this->createContentTypeField('reference');
        $errors = $this->container->get('validator')->validate($ctField);
        $this->assertCount(2, $errors);
        $this->assertEquals('required', $errors->get(0)->getMessageTemplate());
        $this->assertContains('settings.domain', $errors->get(0)->getPropertyPath());
        $this->assertEquals('required', $errors->get(1)->getMessageTemplate());
        $this->assertContains('settings.content_type', $errors->get(1)->getPropertyPath());
    }

    public function testContentTypeFieldTypeWithInvalidSettings() {

        // Content Type Field with invalid settings should not be valid.
        $ctField = $this->createContentTypeField('reference');
        $ctField->setSettings(new FieldableFieldSettings([
            'domain' => 'foo',
            'content_type' => 'baa',
            'view' => 'foo',
            'content_label' => 'laa',
            'foo' => 'baa'
        ]));

        $errors = $this->container->get('validator')->validate($ctField);
        $this->assertCount(1, $errors);
        $this->assertEquals('additional_data', $errors->get(0)->getMessageTemplate());
    }

    public function testContentTypeFieldTypeWithValidSettings() {

        // Content Type Field with invalid settings should not be valid.
        $ctField = $this->createContentTypeField('reference');
        $ctField->setSettings(new FieldableFieldSettings([
            'domain' => 'foo',
            'content_type' => 'baa',
            'view' => 'foo',
            'content_label' => 'laa',
        ]));

        $errors = $this->container->get('validator')->validate($ctField);
        $this->assertCount(0, $errors);
    }

    public function testContentTypeFieldContentLabelFallback() {

        $ctField = $this->createContentTypeField('reference');
        $ctField->getContentType()->getDomain()->setIdentifier('foo');
        $ctField->getContentType()->setIdentifier('baa');

        // Fake organization and domain
        $fieldType = $this->container->get('unite.cms.field_type_manager')->getFieldType($ctField->getType());

        $o1 = new \ReflectionProperty($fieldType, 'authorizationChecker');
        $o1->setAccessible(true);
        $authMock =  $this->createMock(AuthorizationCheckerInterface::class);
        $authMock->expects($this->any())->method('isGranted')->willReturn(true);
        $o1->setValue($fieldType, $authMock);

        $o2 = new \ReflectionProperty($fieldType, 'uniteCMSManager');
        $o2->setAccessible(true);
        $cmsManager = $this->createMock(UniteCMSManager::class);
        $cmsManager->expects($this->any())->method('getOrganization')->willReturn($ctField->getContentType()->getDomain()->getOrganization());
        $cmsManager->expects($this->any())->method('getDomain')->willReturn($ctField->getContentType()->getDomain());
        $o2->setValue($fieldType, $cmsManager);

        $o3 = new \ReflectionProperty($fieldType, 'entityManager');
        $o3->setAccessible(true);
        $viewRepositoryMock = $this->createMock(EntityRepository::class);
        $viewRepositoryMock->expects($this->any())->method('findOneBy')->willReturn($ctField->getContentType()->getView('all'));
        $domainRepositoryMock = $this->createMock(EntityRepository::class);
        $domainRepositoryMock->expects($this->any())->method('findOneBy')->willReturn($ctField->getContentType()->getDomain());

        $cmsManager = $this->createMock(EntityManager::class);
        $cmsManager->expects($this->any())->method('getRepository')->will($this->returnValueMap([
            ['UniteCMSCoreBundle:View', $viewRepositoryMock],
            ['UniteCMSCoreBundle:Domain', $domainRepositoryMock],
        ]));
        $o3->setValue($fieldType, $cmsManager);



        // No content_label fallback and no content_label set.
        $ctField->getContentType()->setContentLabel('');
        $ctField->setSettings(new FieldableFieldSettings([
            'domain' => 'foo',
            'content_type' => 'baa',
            'view' => 'all',
        ]));

        $options = $fieldType->getFormOptions($ctField);
        $this->assertEquals((string)$ctField->getContentType() . ' #{id}', $options['attr']['content-label']);

        // No content_label empty and fallback present
        $ctField->getContentType()->setContentLabel('foo');
        $ctField->setSettings(new FieldableFieldSettings([
            'domain' => 'foo',
            'content_type' => 'baa',
            'view' => 'all',
        ]));

        $options = $fieldType->getFormOptions($ctField);
        $this->assertEquals('foo', $options['attr']['content-label']);

        // content_label present and fallback present
        $ctField->getContentType()->setContentLabel('foo');
        $ctField->setSettings(new FieldableFieldSettings([
            'domain' => 'foo',
            'content_type' => 'baa',
            'content_label' => 'baa',
            'view' => 'all',
        ]));

        $options = $fieldType->getFormOptions($ctField);
        $this->assertEquals('baa', $options['attr']['content-label']);
    }

    public function testReferenceToAnotherDomain() {

        $ctField = $this->createContentTypeField('reference');
        $ctField->setSettings(new FieldableFieldSettings([
            'domain' => 'domain2',
            'content_type' => 'ct2',
        ]));
        $ctField->getContentType()->setIdentifier('ct1');
        $ctField->getContentType()->getDomain()->getContentTypes()->clear();
        $ctField->getContentType()->getDomain()->addContentType($ctField->getContentType());
        $domain2 = new Domain();
        $domain2->setTitle('Domain 2')->setIdentifier("domain2");
        $domain2->setOrganization($ctField->getContentType()->getDomain()->getOrganization());
        $ct2 = new ContentType();
        $ct2->setIdentifier('ct2')->setTitle('CT 2')->setDomain($domain2);

        $this->em->persist($ctField->getContentType()->getDomain()->getOrganization());
        $this->em->persist($ctField->getContentType()->getDomain());
        $this->em->persist($ctField->getContentType());
        $this->em->persist($ctField);
        $this->em->persist($domain2);
        $this->em->persist($ct2);
        $this->em->flush();

        // Inject current domain and org in unite.cms.manager.
        $requestStack = new RequestStack();
        $requestStack->push(new Request([], [], [
            'organization' => $ctField->getContentType()->getDomain()->getOrganization()->getIdentifier(),
            'domain' => $ctField->getContentType()->getDomain()->getIdentifier(),
        ]));

        $user = new User();
        $user->setRoles([User::ROLE_USER])->setName('User');
        $userInDomain1 = new DomainMember();
        $userInDomain1->setDomain($ctField->getContentType()->getDomain());
        $user->addDomain($userInDomain1);
        $userInDomain2 = new DomainMember();
        $userInDomain2->setDomain($domain2);
        $user->addDomain($userInDomain2);
        $this->container->get('security.token_storage')->setToken(new UsernamePasswordToken($user, null, 'main', $user->getRoles()));

        $reflector = new \ReflectionProperty(UniteCMSManager::class, 'requestStack');
        $reflector->setAccessible(true);
        $reflector->setValue($this->container->get('unite.cms.manager'), $requestStack);

        $reflector = new \ReflectionMethod(UniteCMSManager::class, 'initialize');
        $reflector->setAccessible(true);
        $reflector->invoke($this->container->get('unite.cms.manager'));

        $contentSchemaType = $this->container->get('unite.cms.graphql.schema_type_manager')->getSchemaType(
            ucfirst($ctField->getContentType()->getIdentifier()) . 'Content', $ctField->getContentType()->getDomain());

        // If we can get reach this line, no exceptions where thrown during content schema creation.
        $this->assertTrue(true);
        $this->assertGreaterThan(0, $contentSchemaType->getFields());
    }

    /**
     * @expectedException \App\Bundle\CoreBundle\Exception\InvalidFieldConfigurationException
     * @expectedExceptionMessage A reference field was configured to reference to domain "foo". However "foo" does not exist, or you don't have access to it.
     */
    public function testDomainNotFoundException()
    {
        $ctField = $this->createContentTypeField('reference');
        $ctField->setSettings(
            new FieldableFieldSettings(
                [
                    'domain' => 'foo',
                    'content_type' => 'baa',
                ]
            )
        );
        $ctField->getContentType()->setIdentifier('ct1');
        $ctField->getContentType()->getDomain()->getContentTypes()->clear();
        $ctField->getContentType()->getDomain()->addContentType($ctField->getContentType());

        $this->em->persist($ctField->getContentType()->getDomain()->getOrganization());
        $this->em->persist($ctField->getContentType()->getDomain());
        $this->em->persist($ctField->getContentType());
        $this->em->persist($ctField);
        $this->em->flush();

        // Inject current domain and org in unite.cms.manager.
        $requestStack = new RequestStack();
        $requestStack->push(
            new Request(
                [], [], [
                'organization' => $ctField->getContentType()->getDomain()->getOrganization()->getIdentifier(),
                'domain' => $ctField->getContentType()->getDomain()->getIdentifier(),
            ]
            )
        );

        $user = new User();
        $user->setRoles([User::ROLE_USER])->setName('User');
        $userInDomain1 = new DomainMember();
        $userInDomain1->setDomain($ctField->getContentType()->getDomain())->setDomainMemberType($ctField->getContentType()->getDomain()->getDomainMemberTypes()->get('editor'));
        $user->addDomain($userInDomain1);
        $this->container->get('security.token_storage')->setToken(
            new UsernamePasswordToken($user, null, 'main', $user->getRoles())
        );

        $reflector = new \ReflectionProperty(UniteCMSManager::class, 'requestStack');
        $reflector->setAccessible(true);
        $reflector->setValue($this->container->get('unite.cms.manager'), $requestStack);

        $reflector = new \ReflectionMethod(UniteCMSManager::class, 'initialize');
        $reflector->setAccessible(true);
        $reflector->invoke($this->container->get('unite.cms.manager'));

        $this->container
            ->get('unite.cms.field_type_manager')
            ->getFieldType('reference')
            ->getGraphQLType($ctField, $this->container->get('unite.cms.graphql.schema_type_manager'));
    }

    /**
     * @expectedException \App\Bundle\CoreBundle\Exception\InvalidFieldConfigurationException
     * @expectedExceptionMessage A reference field was configured to reference to content type "baa" on domain "domain1". However "baa" does not exist.
     */
    public function testContentTypeNotFoundException()
    {
        $ctField = $this->createContentTypeField('reference');
        $ctField->getContentType()->getDomain()->setIdentifier('domain1');
        $ctField->setSettings(
            new FieldableFieldSettings(
                [
                    'domain' => $ctField->getContentType()->getDomain()->getIdentifier(),
                    'content_type' => 'baa',
                ]
            )
        );
        $ctField->getContentType()->setIdentifier('ct1');
        $ctField->getContentType()->getDomain()->getContentTypes()->clear();
        $ctField->getContentType()->getDomain()->addContentType($ctField->getContentType());

        $this->em->persist($ctField->getContentType()->getDomain()->getOrganization());
        $this->em->persist($ctField->getContentType()->getDomain());
        $this->em->persist($ctField->getContentType());
        $this->em->persist($ctField);
        $this->em->flush();

        // Inject current domain and org in unite.cms.manager.
        $requestStack = new RequestStack();
        $requestStack->push(
            new Request(
                [], [], [
                    'organization' => $ctField->getContentType()->getDomain()->getOrganization()->getIdentifier(),
                    'domain' => $ctField->getContentType()->getDomain()->getIdentifier(),
                ]
            )
        );

        $user = new User();
        $user->setRoles([User::ROLE_USER])->setName('User');
        $userInDomain1 = new DomainMember();
        $userInDomain1->setDomain($ctField->getContentType()->getDomain())->setDomainMemberType($ctField->getContentType()->getDomain()->getDomainMemberTypes()->get('editor'));
        $user->addDomain($userInDomain1);
        $this->container->get('security.token_storage')->setToken(
            new UsernamePasswordToken($user, null, 'main', $user->getRoles())
        );

        $reflector = new \ReflectionProperty(UniteCMSManager::class, 'requestStack');
        $reflector->setAccessible(true);
        $reflector->setValue($this->container->get('unite.cms.manager'), $requestStack);

        $reflector = new \ReflectionMethod(UniteCMSManager::class, 'initialize');
        $reflector->setAccessible(true);
        $reflector->invoke($this->container->get('unite.cms.manager'));

        $this->container
            ->get('unite.cms.field_type_manager')
            ->getFieldType('reference')
            ->getGraphQLType($ctField, $this->container->get('unite.cms.graphql.schema_type_manager'));
    }

    /**
     * @expectedException \App\Bundle\CoreBundle\Exception\DomainAccessDeniedException
     * @expectedExceptionMessage A reference field was configured to reference to domain "domain1". However you are not allowed to access it.
     */
    public function testDomainNoAccess()
    {
        $ctField = $this->createContentTypeField('reference');
        $ctField->getContentType()->getDomain()->setIdentifier('domain1');
        $ctField->setSettings(
            new FieldableFieldSettings(
                [
                    'domain' => $ctField->getContentType()->getDomain()->getIdentifier(),
                    'content_type' => 'ct1',
                ]
            )
        );
        $ctField->getContentType()->setIdentifier('ct1');
        $ctField->getContentType()->getDomain()->getContentTypes()->clear();
        $ctField->getContentType()->getDomain()->addContentType($ctField->getContentType());

        $this->em->persist($ctField->getContentType()->getDomain()->getOrganization());
        $this->em->persist($ctField->getContentType()->getDomain());
        $this->em->persist($ctField->getContentType());
        $this->em->persist($ctField);
        $this->em->flush();

        // Inject current domain and org in unite.cms.manager.
        $requestStack = new RequestStack();
        $requestStack->push(
            new Request(
                [], [], [
                    'organization' => $ctField->getContentType()->getDomain()->getOrganization()->getIdentifier(),
                    'domain' => $ctField->getContentType()->getDomain()->getIdentifier(),
                ]
            )
        );

        $user = new User();
        $user->setRoles([User::ROLE_USER])->setName('User');
        $this->container->get('security.token_storage')->setToken(
            new UsernamePasswordToken($user, null, 'main', $user->getRoles())
        );

        $reflector = new \ReflectionProperty(UniteCMSManager::class, 'requestStack');
        $reflector->setAccessible(true);
        $reflector->setValue($this->container->get('unite.cms.manager'), $requestStack);

        $reflector = new \ReflectionMethod(UniteCMSManager::class, 'initialize');
        $reflector->setAccessible(true);
        $reflector->invoke($this->container->get('unite.cms.manager'));

        $this->container
            ->get('unite.cms.field_type_manager')
            ->getFieldType('reference')
            ->getGraphQLType($ctField, $this->container->get('unite.cms.graphql.schema_type_manager'));
    }

    /**
     * @expectedException \App\Bundle\CoreBundle\Exception\ContentTypeAccessDeniedException
     * @expectedExceptionMessage You are not allowed to list content of content type "ct1" on domain "domain1".
     */
    public function testContentTypeNoAccess()
    {
        $ctField = $this->createContentTypeField('reference');
        $ctField->getContentType()->getDomain()->setIdentifier('domain1');
        $ctField->getContentType()->addPermission(ContentVoter::LIST, 'false');
        $ctField->setSettings(
            new FieldableFieldSettings(
                [
                    'domain' => $ctField->getContentType()->getDomain()->getIdentifier(),
                    'content_type' => 'ct1',
                ]
            )
        );
        $ctField->getContentType()->setIdentifier('ct1');
        $ctField->getContentType()->getDomain()->getContentTypes()->clear();
        $ctField->getContentType()->getDomain()->addContentType($ctField->getContentType());

        $this->em->persist($ctField->getContentType()->getDomain()->getOrganization());
        $this->em->persist($ctField->getContentType()->getDomain());
        $this->em->persist($ctField->getContentType());
        $this->em->persist($ctField);
        $this->em->flush();

        // Inject current domain and org in unite.cms.manager.
        $requestStack = new RequestStack();
        $requestStack->push(
            new Request(
                [], [], [
                    'organization' => $ctField->getContentType()->getDomain()->getOrganization()->getIdentifier(),
                    'domain' => $ctField->getContentType()->getDomain()->getIdentifier(),
                ]
            )
        );

        $user = new User();
        $user->setRoles([User::ROLE_USER])->setName('User');
        $userInDomain1 = new DomainMember();
        $userInDomain1->setDomain($ctField->getContentType()->getDomain())->setDomainMemberType($ctField->getContentType()->getDomain()->getDomainMemberTypes()->get('viewer'));
        $user->addDomain($userInDomain1);

        $this->container->get('security.token_storage')->setToken(
            new UsernamePasswordToken($user, null, 'main', $user->getRoles())
        );

        $reflector = new \ReflectionProperty(UniteCMSManager::class, 'requestStack');
        $reflector->setAccessible(true);
        $reflector->setValue($this->container->get('unite.cms.manager'), $requestStack);

        $reflector = new \ReflectionMethod(UniteCMSManager::class, 'initialize');
        $reflector->setAccessible(true);
        $reflector->invoke($this->container->get('unite.cms.manager'));

        $this->container
            ->get('unite.cms.field_type_manager')
            ->getFieldType('reference')
            ->getGraphQLType($ctField, $this->container->get('unite.cms.graphql.schema_type_manager'));
    }
}

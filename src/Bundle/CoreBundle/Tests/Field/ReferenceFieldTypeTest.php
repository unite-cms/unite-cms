<?php

namespace UniteCMS\CoreBundle\Tests\Field;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Router;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\Context\ExecutionContext;
use UniteCMS\CoreBundle\Entity\ApiKey;
use UniteCMS\CoreBundle\Entity\ContentType;
use UniteCMS\CoreBundle\Entity\Domain;
use UniteCMS\CoreBundle\Entity\DomainMember;
use UniteCMS\CoreBundle\Entity\User;
use UniteCMS\CoreBundle\Entity\View;
use UniteCMS\CoreBundle\Field\FieldableFieldSettings;
use UniteCMS\CoreBundle\ParamConverter\IdentifierNormalizer;
use UniteCMS\CoreBundle\Security\Voter\ContentVoter;
use UniteCMS\CoreBundle\Service\UniteCMSManager;
use UniteCMS\CoreBundle\View\ViewParameterBag;

class ReferenceFieldTypeTest extends FieldTypeTestCase
{
    public function testContentTypeFieldTypeWithEmptySettings() {

        // Content Type Field with empty settings should not be valid.
        $ctField = $this->createContentTypeField('reference');
        $errors = static::$container->get('validator')->validate($ctField);
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
            'foo' => 'baa',
            'not_empty' => '123',
        ]));

        $errors = static::$container->get('validator')->validate($ctField);
        $this->assertCount(2, $errors);
        $this->assertEquals('additional_data', $errors->get(0)->getMessageTemplate());
        $this->assertEquals('noboolean_value', $errors->get(1)->getMessageTemplate());
    }

    public function testContentTypeFieldTypeWithInvalidDomainAndContentTypeSetting() {

        // Content Type Field with invalid settings should not be valid.
        $ctField = $this->createContentTypeField('reference');
        $ctField->getContentType()->getDomain()->getOrganization()->setIdentifier('luu_luu');
        $ctField->getContentType()->getDomain()->setIdentifier('foo')->setTitle('Foo');
        $ctField->getContentType()->setIdentifier('baa');

        $this->em->persist($ctField->getContentType()->getDomain()->getOrganization());
        $this->em->persist($ctField->getContentType()->getDomain());
        $this->em->persist($ctField->getContentType());
        $this->em->persist($ctField);
        $this->em->flush();
        $this->em->refresh($ctField->getContentType()->getDomain());

        $domain = $ctField->getContentType()->getDomain();

        $this->assertNotNull($ctField->getContentType()->getDomain()->getId());

        $ctField->setSettings(new FieldableFieldSettings([
            'domain' => 'foo234',
            'content_type' => 'baa2323',
            'view' => 'foo',
            'content_label' => 'laa',
        ]));

        // Fake organization
        $fieldType = static::$container->get('unite.cms.field_type_manager')->getFieldType($ctField->getType());

        $o01 = new \ReflectionProperty($fieldType, 'referenceResolver');
        $o01->setAccessible(true);
        $referenceResolver = $o01->getValue($fieldType);

        $o1 = new \ReflectionProperty($referenceResolver, 'authorizationChecker');
        $o1->setAccessible(true);
        $authMock =  $this->createMock(AuthorizationCheckerInterface::class);
        $authMock->expects($this->any())->method('isGranted')->willReturn(true);
        $o1->setValue($referenceResolver, $authMock);

        $reflector = new \ReflectionProperty(UniteCMSManager::class, 'organization');
        $reflector->setAccessible(true);
        $reflector->setValue(static::$container->get('unite.cms.manager'), $ctField->getContentType()->getDomain()->getOrganization());

        $reflector = new \ReflectionProperty(UniteCMSManager::class, 'domain');
        $reflector->setAccessible(true);
        $reflector->setValue(static::$container->get('unite.cms.manager'), $ctField->getContentType()->getDomain());

        // ContentType and Domain does not exist.
        $errors = static::$container->get('validator')->validate($domain);
        $this->assertEquals('invalid_domain', $errors->get(0)->getMessageTemplate());

        // Domain exist, content type does not exist.
        $ctField->getSettings()->domain = 'foo';
        $errors = static::$container->get('validator')->validate($domain);
        $this->assertEquals('invalid_content_type', $errors->get(0)->getMessageTemplate());

        // Content type exist, domain does not exist.
        $ctField->getSettings()->domain = 'wrong';
        $ctField->getSettings()->content_type = 'baa';
        $errors = static::$container->get('validator')->validate($domain);
        $this->assertEquals('invalid_domain', $errors->get(0)->getMessageTemplate());

        // No view access on domain.
        $ctField->getSettings()->domain = 'foo';
        $ctField->getSettings()->content_type = 'baa';

        $o1->setValue($referenceResolver, new class implements AuthorizationCheckerInterface {
            public function isGranted($attributes, $subject = null) { return false; }
        });

        $errors = static::$container->get('validator')->validate($domain);
        $this->assertCount(1, $errors);
        $this->assertEquals('invalid_domain', $errors->get(0)->getMessageTemplate());

        $o1->setValue($referenceResolver, new class implements AuthorizationCheckerInterface {
            public function isGranted($attributes, $subject = null) { return true; }
        });

        $this->assertCount(0, static::$container->get('validator')->validate($domain));

        // Test if this domain is currently updated for the new content_type but not saved.
        $contentType = new ContentType();
        $contentType->setIdentifier('new_ct')->setTitle('new_ct');
        $domain->addContentType($contentType);

        $ctField->getSettings()->domain = 'foo';
        $ctField->getSettings()->content_type = 'wrong_new_ct';
        $errors = static::$container->get('validator')->validate($domain);

        $this->assertCount(1, $errors);
        $this->assertEquals('invalid_content_type', $errors->get(0)->getMessageTemplate());

        $ctField->getSettings()->domain = 'foo';
        $ctField->getSettings()->content_type = 'new_ct';
        $this->assertCount(0, static::$container->get('validator')->validate($domain));

        // Test for freshly created domains.
        $ctField = $this->createContentTypeField('reference');
        $ctField->getContentType()->getDomain()->getOrganization()->setIdentifier('luu_luu');
        $ctField->getContentType()->getDomain()->setIdentifier('new')->setTitle('Foo');
        $ctField->getContentType()->setIdentifier('baa');
        $domain = $ctField->getContentType()->getDomain();

        // wrong domain name and wrong content_type
        $ctField->getSettings()->domain = 'wrong';
        $ctField->getSettings()->content_type = 'wrong';
        $errors = static::$container->get('validator')->validate($domain);
        $this->assertEquals('invalid_domain', $errors->get(0)->getMessageTemplate());

        // wrong content_type
        $ctField->getSettings()->domain = 'new';
        $errors = static::$container->get('validator')->validate($domain);
        $this->assertEquals('invalid_content_type', $errors->get(0)->getMessageTemplate());

        // wrong domain
        $ctField->getSettings()->domain = 'wrong';
        $ctField->getSettings()->content_type = 'baa';
        $errors = static::$container->get('validator')->validate($domain);
        $this->assertEquals('invalid_domain', $errors->get(0)->getMessageTemplate());

        // Correct content_type and domain
        $ctField->getSettings()->domain = 'new';
        $this->assertCount(0, static::$container->get('validator')->validate($domain));

    }

    public function testContentTypeFieldTypeWithValidSettings() {

        // Content Type Field with invalid settings should not be valid.
        $ctField = $this->createContentTypeField('reference');

        $ctField->getContentType()->getDomain()->getOrganization()->setIdentifier('luu_luu');
        $ctField->getContentType()->getDomain()->setIdentifier('foo');

        $ctField->setSettings(new FieldableFieldSettings([
            'domain' => 'foo',
            'content_type' => 'baa',
            'view' => 'foo',
            'content_label' => 'laa',
            'not_empty' => true,
        ]));

        // Fake organization
        $fieldType = static::$container->get('unite.cms.field_type_manager')->getFieldType($ctField->getType());

        $o01 = new \ReflectionProperty($fieldType, 'referenceResolver');
        $o01->setAccessible(true);
        $referenceResolver = $o01->getValue($fieldType);

        $o1 = new \ReflectionProperty($referenceResolver, 'authorizationChecker');
        $o1->setAccessible(true);
        $authMock =  $this->createMock(AuthorizationCheckerInterface::class);
        $authMock->expects($this->any())->method('isGranted')->willReturn(true);
        $o1->setValue($referenceResolver, $authMock);

        $domain = new Domain();
        $domain->setTitle('Domain')->setIdentifier("domain");
        $domain->setOrganization($ctField->getContentType()->getDomain()->getOrganization());

        $contentType = new ContentType();
        $contentType->setIdentifier('baa')->setTitle('Baaa')->setDescription('TEST')->setDomain($domain);

        $ctField->getContentType()->getDomain()->getContentTypes()->clear();
        $ctField->getContentType()->getDomain()->addContentType($contentType);

        $this->em->persist($ctField->getContentType()->getDomain()->getOrganization());
        $this->em->persist($ctField->getContentType()->getDomain());
        $this->em->persist($ctField->getContentType());
        $this->em->persist($ctField);
        $this->em->persist($domain);
        $this->em->persist($contentType);
        $this->em->flush();

        $reflector = new \ReflectionProperty(UniteCMSManager::class, 'organization');
        $reflector->setAccessible(true);
        $reflector->setValue(static::$container->get('unite.cms.manager'), $ctField->getContentType()->getDomain()->getOrganization());

        $reflector = new \ReflectionProperty(UniteCMSManager::class, 'domain');
        $reflector->setAccessible(true);
        $reflector->setValue(static::$container->get('unite.cms.manager'), $ctField->getContentType()->getDomain());

        $errors = static::$container->get('validator')->validate($ctField);
        $this->assertCount(0, $errors);

        // Now, when I change the domain identifier, the same settings should not be valid anymore.
        $ctField->getContentType()->getDomain()->setIdentifier($ctField->getContentType()->getDomain()->getIdentifier() . '_updated');
        $errors = static::$container->get('validator')->validate($ctField);
        $this->assertCount(1, $errors);
        $this->assertEquals('Invalid Domain given. The Domain does not exist or Access is prohibited', $errors->get(0)->getMessage());
    }

    public function testFormOptionGeneration() {

        $ctField = $this->createContentTypeField('reference');
        $ctField->getContentType()->getDomain()->getOrganization()->setIdentifier('luu_luu');
        $ctField->getContentType()->getDomain()->setIdentifier('foo_foo');
        $ctField->getContentType()->setIdentifier('baa_baa');
        $ctField->setSettings(new FieldableFieldSettings([
            'domain' => 'foo_foo',
            'content_type' => 'baa_baa',
            'content_label' => 'laa',
        ]));

        // Fake organization and domain
        $fieldType = static::$container->get('unite.cms.field_type_manager')->getFieldType($ctField->getType());

        $o01 = new \ReflectionProperty($fieldType, 'referenceResolver');
        $o01->setAccessible(true);
        $referenceResolver = $o01->getValue($fieldType);

        $o1 = new \ReflectionProperty($referenceResolver, 'authorizationChecker');
        $o1->setAccessible(true);
        $authMock =  $this->createMock(AuthorizationCheckerInterface::class);
        $authMock->expects($this->any())->method('isGranted')->willReturn(true);
        $o1->setValue($referenceResolver, $authMock);

        $o10 = new \ReflectionProperty($fieldType, 'authorizationChecker');
        $o10->setAccessible(true);
        $authMock =  $this->createMock(AuthorizationCheckerInterface::class);
        $authMock->expects($this->any())->method('isGranted')->willReturn(true);
        $o10->setValue($fieldType, $authMock);

        $o2 = new \ReflectionProperty($referenceResolver, 'uniteCMSManager');
        $o2->setAccessible(true);
        $cmsManager = $this->createMock(UniteCMSManager::class);
        $cmsManager->expects($this->any())->method('getOrganization')->willReturn($ctField->getContentType()->getDomain()->getOrganization());
        $cmsManager->expects($this->any())->method('getDomain')->willReturn($ctField->getContentType()->getDomain());
        $o2->setValue($referenceResolver, $cmsManager);

        $o3 = new \ReflectionProperty($fieldType, 'entityManager');
        $o3->setAccessible(true);
        $o31 = new \ReflectionProperty($referenceResolver, 'entityManager');
        $o31->setAccessible(true);

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
        $o31->setValue($referenceResolver, $cmsManager);

        $options = $fieldType->getFormOptions($ctField);
        $this->assertEquals(false, $options['required']);
        $this->assertEquals([
            'domain' => 'foo_foo',
            'content_type' => 'baa_baa',
        ], $options['empty_data']);
        $this->assertEquals('laa', $options['attr']['content-label']);
        $this->assertEquals(static::$container->get('router')->generate(
            'unitecms_core_api',
            [
                'organization' => 'luu-luu',
                'domain' => 'foo-foo',
            ],
            Router::ABSOLUTE_URL
        ), $options['attr']['api-url']);
    }

    public function testContentTypeFieldContentLabelFallback() {

        $ctField = $this->createContentTypeField('reference');
        $ctField->getContentType()->getDomain()->setIdentifier('foo');
        $ctField->getContentType()->setIdentifier('baa');

        // Fake organization and domain
        $fieldType = static::$container->get('unite.cms.field_type_manager')->getFieldType($ctField->getType());

        $o01 = new \ReflectionProperty($fieldType, 'referenceResolver');
        $o01->setAccessible(true);
        $referenceResolver = $o01->getValue($fieldType);

        $o1 = new \ReflectionProperty($referenceResolver, 'authorizationChecker');
        $o1->setAccessible(true);
        $authMock =  $this->createMock(AuthorizationCheckerInterface::class);
        $authMock->expects($this->any())->method('isGranted')->willReturn(true);
        $o1->setValue($referenceResolver, $authMock);

        $o10 = new \ReflectionProperty($fieldType, 'authorizationChecker');
        $o10->setAccessible(true);
        $authMock =  $this->createMock(AuthorizationCheckerInterface::class);
        $authMock->expects($this->any())->method('isGranted')->willReturn(true);
        $o10->setValue($fieldType, $authMock);

        $o2 = new \ReflectionProperty($referenceResolver, 'uniteCMSManager');
        $o2->setAccessible(true);
        $cmsManager = $this->createMock(UniteCMSManager::class);
        $cmsManager->expects($this->any())->method('getOrganization')->willReturn($ctField->getContentType()->getDomain()->getOrganization());
        $cmsManager->expects($this->any())->method('getDomain')->willReturn($ctField->getContentType()->getDomain());
        $o2->setValue($referenceResolver, $cmsManager);

        $o3 = new \ReflectionProperty($fieldType, 'entityManager');
        $o3->setAccessible(true);
        $o31 = new \ReflectionProperty($referenceResolver, 'entityManager');
        $o31->setAccessible(true);
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
        $o31->setValue($referenceResolver, $cmsManager);

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
            'organization' => IdentifierNormalizer::denormalize($ctField->getContentType()->getDomain()->getOrganization()->getIdentifier()),
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
        static::$container->get('security.token_storage')->setToken(new UsernamePasswordToken($user, null, 'main', $user->getRoles()));

        $reflector = new \ReflectionProperty(UniteCMSManager::class, 'requestStack');
        $reflector->setAccessible(true);
        $reflector->setValue(static::$container->get('unite.cms.manager'), $requestStack);

        $reflector = new \ReflectionMethod(UniteCMSManager::class, 'initialize');
        $reflector->setAccessible(true);
        $reflector->invoke(static::$container->get('unite.cms.manager'));

        $contentSchemaType = static::$container->get('unite.cms.graphql.schema_type_manager')->getSchemaType(
            ucfirst($ctField->getContentType()->getIdentifier()) . 'Content', $ctField->getContentType()->getDomain());

        // If we can get reach this line, no exceptions where thrown during content schema creation.
        $this->assertTrue(true);
        $this->assertGreaterThan(0, $contentSchemaType->getFields());
    }

    /**
     * @expectedException \UniteCMS\CoreBundle\Exception\InvalidFieldConfigurationException
     * @expectedExceptionMessage A reference field was configured with domain "foo". However "foo" does not exist, or you don't have access to it.
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
                'organization' => IdentifierNormalizer::denormalize($ctField->getContentType()->getDomain()->getOrganization()->getIdentifier()),
                'domain' => $ctField->getContentType()->getDomain()->getIdentifier(),
            ]
            )
        );

        $user = new User();
        $user->setRoles([User::ROLE_USER])->setName('User');
        $userInDomain1 = new DomainMember();
        $userInDomain1->setDomain($ctField->getContentType()->getDomain())->setDomainMemberType($ctField->getContentType()->getDomain()->getDomainMemberTypes()->get('editor'));
        $user->addDomain($userInDomain1);
        static::$container->get('security.token_storage')->setToken(
            new UsernamePasswordToken($user, null, 'main', $user->getRoles())
        );

        $reflector = new \ReflectionProperty(UniteCMSManager::class, 'requestStack');
        $reflector->setAccessible(true);
        $reflector->setValue(static::$container->get('unite.cms.manager'), $requestStack);

        $reflector = new \ReflectionMethod(UniteCMSManager::class, 'initialize');
        $reflector->setAccessible(true);
        $reflector->invoke(static::$container->get('unite.cms.manager'));

        static::$container
            ->get('unite.cms.field_type_manager')
            ->getFieldType('reference')
            ->getGraphQLType($ctField, static::$container->get('unite.cms.graphql.schema_type_manager'));
    }

    /**
     * @expectedException \UniteCMS\CoreBundle\Exception\InvalidFieldConfigurationException
     * @expectedExceptionMessage A reference field was configured with content type "baa" on domain "domain1". However "baa" does not exist.
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
                    'organization' => IdentifierNormalizer::denormalize($ctField->getContentType()->getDomain()->getOrganization()->getIdentifier()),
                    'domain' => $ctField->getContentType()->getDomain()->getIdentifier(),
                ]
            )
        );

        $user = new User();
        $user->setRoles([User::ROLE_USER])->setName('User');
        $userInDomain1 = new DomainMember();
        $userInDomain1->setDomain($ctField->getContentType()->getDomain())->setDomainMemberType($ctField->getContentType()->getDomain()->getDomainMemberTypes()->get('editor'));
        $user->addDomain($userInDomain1);
        static::$container->get('security.token_storage')->setToken(
            new UsernamePasswordToken($user, null, 'main', $user->getRoles())
        );

        $reflector = new \ReflectionProperty(UniteCMSManager::class, 'requestStack');
        $reflector->setAccessible(true);
        $reflector->setValue(static::$container->get('unite.cms.manager'), $requestStack);

        $reflector = new \ReflectionMethod(UniteCMSManager::class, 'initialize');
        $reflector->setAccessible(true);
        $reflector->invoke(static::$container->get('unite.cms.manager'));

        static::$container
            ->get('unite.cms.field_type_manager')
            ->getFieldType('reference')
            ->getGraphQLType($ctField, static::$container->get('unite.cms.graphql.schema_type_manager'));
    }

    /**
     * @expectedException \UniteCMS\CoreBundle\Exception\DomainAccessDeniedException
     * @expectedExceptionMessage A reference field was configured with domain "domain1". However you are not allowed to access it.
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
                    'organization' => IdentifierNormalizer::denormalize($ctField->getContentType()->getDomain()->getOrganization()->getIdentifier()),
                    'domain' => $ctField->getContentType()->getDomain()->getIdentifier(),
                ]
            )
        );

        $user = new User();
        $user->setRoles([User::ROLE_USER])->setName('User');
        static::$container->get('security.token_storage')->setToken(
            new UsernamePasswordToken($user, null, 'main', $user->getRoles())
        );

        $reflector = new \ReflectionProperty(UniteCMSManager::class, 'requestStack');
        $reflector->setAccessible(true);
        $reflector->setValue(static::$container->get('unite.cms.manager'), $requestStack);

        $reflector = new \ReflectionMethod(UniteCMSManager::class, 'initialize');
        $reflector->setAccessible(true);
        $reflector->invoke(static::$container->get('unite.cms.manager'));

        static::$container
            ->get('unite.cms.field_type_manager')
            ->getFieldType('reference')
            ->getGraphQLType($ctField, static::$container->get('unite.cms.graphql.schema_type_manager'));
    }

    /**
     * @expectedException \UniteCMS\CoreBundle\Exception\ContentTypeAccessDeniedException
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
                    'organization' => IdentifierNormalizer::denormalize($ctField->getContentType()->getDomain()->getOrganization()->getIdentifier()),
                    'domain' => $ctField->getContentType()->getDomain()->getIdentifier(),
                ]
            )
        );

        $user = new User();
        $user->setRoles([User::ROLE_USER])->setName('User');
        $userInDomain1 = new DomainMember();
        $userInDomain1->setDomain($ctField->getContentType()->getDomain())->setDomainMemberType($ctField->getContentType()->getDomain()->getDomainMemberTypes()->get('viewer'));
        $user->addDomain($userInDomain1);

        static::$container->get('security.token_storage')->setToken(
            new UsernamePasswordToken($user, null, 'main', $user->getRoles())
        );

        $reflector = new \ReflectionProperty(UniteCMSManager::class, 'requestStack');
        $reflector->setAccessible(true);
        $reflector->setValue(static::$container->get('unite.cms.manager'), $requestStack);

        $reflector = new \ReflectionMethod(UniteCMSManager::class, 'initialize');
        $reflector->setAccessible(true);
        $reflector->invoke(static::$container->get('unite.cms.manager'));

        static::$container
            ->get('unite.cms.field_type_manager')
            ->getFieldType('reference')
            ->getGraphQLType($ctField, static::$container->get('unite.cms.graphql.schema_type_manager'));
    }
}

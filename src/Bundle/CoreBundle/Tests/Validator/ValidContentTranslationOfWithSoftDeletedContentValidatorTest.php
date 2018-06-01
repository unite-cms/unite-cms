<?php

namespace UniteCMS\CoreBundle\Tests\Validator;

use Doctrine\ORM\PersistentCollection;
use UniteCMS\CoreBundle\Entity\Content;
use UniteCMS\CoreBundle\Entity\ContentType;
use UniteCMS\CoreBundle\Entity\Domain;
use UniteCMS\CoreBundle\Entity\Organization;
use UniteCMS\CoreBundle\Tests\DatabaseAwareTestCase;

class ValidContentTranslationOfWithSoftDeletedContentValidatorTest extends DatabaseAwareTestCase
{

    public function testSoftDeletedValue() {

        $org = new Organization();
        $org->setIdentifier('any')->setTitle('any');
        $domain = new Domain();
        $domain->setOrganization($org)->setIdentifier('any')->setTitle('any');
        $contentType = new ContentType();
        $contentType->setDomain($domain)->setIdentifier('any')->setTitle('any')->setLocales(['de', 'en']);

        $value = new Content();
        $value->setLocale('de')->setContentType($contentType);
        $object = new Content();
        $object->setLocale('de')->setContentType($contentType)->setTranslationOf($value);

        $this->em->persist($org);
        $this->em->persist($domain);
        $this->em->persist($contentType);

        $this->em->flush();

        $this->em->persist($object);
        $this->em->persist($value);

        $this->em->flush();

        $this->em->remove($value);

        $this->em->flush();

        // To check half loaded soft deleted content, we need to clear all current loaded entity manager entities...
        $this->em->clear();

        // And reload our main object.
        $reloadedObject = $this->em->getRepository('UniteCMSCoreBundle:Content')->find($object->getId());

        $errors = static::$container->get('validator')->validate($reloadedObject);
        $this->assertCount(1, $errors);
        $this->assertEquals('unique_translations', $errors->get(0)->getMessageTemplate());

        $reloadedObject->setLocale('en');
        $this->em->persist($reloadedObject);
        $errors = static::$container->get('validator')->validate($reloadedObject);
        $this->assertCount(0, $errors);
    }
}

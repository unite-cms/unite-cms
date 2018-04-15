<?php

namespace UniteCMS\CoreBundle\Tests\Field;

use UniteCMS\CoreBundle\Entity\ContentType;
use UniteCMS\CoreBundle\Entity\ContentTypeField;
use UniteCMS\CoreBundle\Entity\Domain;
use UniteCMS\CoreBundle\Entity\Organization;
use UniteCMS\CoreBundle\Entity\SettingType;
use UniteCMS\CoreBundle\Entity\SettingTypeField;
use UniteCMS\CoreBundle\Tests\DatabaseAwareTestCase;

abstract class FieldTypeTestCase extends DatabaseAwareTestCase
{
    protected function createContentTypeField(string $type): ContentTypeField
    {
        $field = new ContentTypeField();
        $field
            ->setType($type)
            ->setTitle($this->generateRandomMachineName(100))
            ->setIdentifier($this->generateRandomMachineName(100))
            ->setContentType(new ContentType())
            ->getContentType()
            ->setTitle($this->generateRandomMachineName(100))
            ->setIdentifier($this->generateRandomMachineName(100))
            ->setDomain(new Domain())
            ->getDomain()
            ->setTitle($this->generateRandomMachineName(100))
            ->setIdentifier($this->generateRandomMachineName(100))
            ->setOrganization(new Organization())
            ->getOrganization()
            ->setTitle($this->generateRandomMachineName(100))
            ->setIdentifier($this->generateRandomMachineName(100));

        return $field;
    }

    protected function createSettingTypeField(string $type): SettingTypeField
    {
        $field = new SettingTypeField();
        $field
            ->setType($type)
            ->setTitle($this->generateRandomMachineName(100))
            ->setIdentifier($this->generateRandomMachineName(100))
            ->setSettingType(new SettingType())
            ->getSettingType()
            ->setTitle($this->generateRandomMachineName(100))
            ->setIdentifier($this->generateRandomMachineName(100))
            ->setDomain(new Domain())
            ->getDomain()
            ->setTitle($this->generateRandomMachineName(100))
            ->setIdentifier($this->generateRandomMachineName(100))
            ->setOrganization(new Organization())
            ->getOrganization()
            ->setTitle($this->generateRandomMachineName(100))
            ->setIdentifier($this->generateRandomMachineName(100));

        return $field;
    }
}

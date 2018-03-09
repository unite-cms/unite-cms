<?php

namespace UnitedCMS\CoreBundle\Tests\Field;

use UnitedCMS\CoreBundle\Entity\ContentType;
use UnitedCMS\CoreBundle\Entity\ContentTypeField;
use UnitedCMS\CoreBundle\Entity\Domain;
use UnitedCMS\CoreBundle\Entity\Organization;
use UnitedCMS\CoreBundle\Entity\SettingType;
use UnitedCMS\CoreBundle\Entity\SettingTypeField;
use UnitedCMS\CoreBundle\Tests\DatabaseAwareTestCase;

abstract class FieldTypeTestCase extends DatabaseAwareTestCase
{
    protected function createContentTypeField(string $type) : ContentTypeField {
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

    protected function createSettingTypeField(string $type) : SettingTypeField {
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
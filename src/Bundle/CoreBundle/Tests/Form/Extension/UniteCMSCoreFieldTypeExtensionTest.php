<?php
/**
 * Created by PhpStorm.
 * User: stefankamsker
 * Date: 08.11.18
 * Time: 11:00
 */

namespace UniteCMS\CoreBundle\Tests\Form\Extension;

use UniteCMS\CoreBundle\Tests\Field\FieldTypeTestCase;
use UniteCMS\CoreBundle\Entity\Content;
use UniteCMS\CoreBundle\Field\FieldableFieldSettings;

class UniteCMSCoreFieldTypeExtensionTest extends FieldTypeTestCase
{

    public function testUniteCMSCoreFieldTypeExtension() {

        $field_types = [ 'checkbox', 'choice', 'choices', 'date', 'datetime', 'email', 'integer', 'link', 'number', 'phone', 'range', 'text', 'textarea'];

        foreach ($field_types as $field_type) {

            $ctField = $this->createContentTypeField($field_type);

            $ctField->setIdentifier('f1'.$field_type);
            $ctField->getContentType()
                ->getDomain()
                ->getOrganization()
                ->setIdentifier('baa');
            $ctField->getContentType()->getDomain()->setIdentifier('foo');
            $ctField->getContentType()->setIdentifier('ct');

            $ctField->setSettings(
                new FieldableFieldSettings(
                    [
                        'description' => 'test'.$ctField->getIdentifier()
                    ]
                )
            );

            $content = new Content();
            $content->setContentType($ctField->getContentType());

            $form = static::$container->get('unite.cms.fieldable_form_builder')
                ->createForm(
                    $ctField->getContentType(),
                    $content
                );


            $this->assertEquals('test'.$ctField->getIdentifier(), $form->get($ctField->getIdentifier())->getConfig()->getOption('description'));

        }

    }

}
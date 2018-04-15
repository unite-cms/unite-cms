<?php

namespace UniteCMS\CoreBundle\Tests\Functional;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use UniteCMS\CoreBundle\Entity\Fieldable;
use UniteCMS\CoreBundle\Entity\FieldableContent;
use UniteCMS\CoreBundle\Entity\FieldableField;
use UniteCMS\CoreBundle\Field\FieldTypeInterface;
use UniteCMS\CoreBundle\Form\FieldableFormBuilder;
use UniteCMS\CoreBundle\Form\FieldableFormField;
use UniteCMS\CoreBundle\Form\FieldableFormType;
use UniteCMS\CoreBundle\Tests\ContainerAwareTestCase;

class FieldableFormBuilderTest extends ContainerAwareTestCase
{

    public function testFormBuilderAvailable()
    {
        $this->assertTrue($this->container->has('unite.cms.fieldable_form_builder'));
        $this->assertInstanceOf(FieldableFormBuilder::class, $this->container->get('unite.cms.fieldable_form_builder'));
    }

    public function testFormBuilderBuildForm()
    {

        $fieldable = new class implements Fieldable
        {
            public function getFields()
            {
                return [
                    new class implements FieldableField
                    {
                        public function getEntity()
                        {
                            return $this->entity;
                        }

                        public function setEntity($entity)
                        {
                            $this->entity = $entity;
                        }

                        public function getType()
                        {
                            return 'text';
                        }

                        public function getIdentifier()
                        {
                            return 'field1';
                        }

                        public function getTitle()
                        {
                            return 'Field 1';
                        }

                        public function getSettings()
                        {
                            return [];
                        }

                        public function getJsonExtractIdentifier()
                        {
                            return '$.'.$this->getIdentifier();
                        }
                    },
                ];
            }

            public function setFields($fields)
            {
            }

            public function addField(FieldableField $field)
            {
            }

            public function getLocales(): array
            {
                return [];
            }

            public function getIdentifier()
            {
                return '';
            }

            public function getIdentifierPath($delimiter = '/')
            {
                return $this->getIdentifier();
            }

            public function getParentEntity()
            {
                return null;
            }

            public function getRootEntity(): Fieldable
            {
                return $this;
            }
        };
        $content = new class implements FieldableContent
        {
            private $data = ['field1' => 'Any Value'];

            public function setData(array $data)
            {
                $this->data = $data;
            }

            public function getData(): array
            {
                return $this->data;
            }

            public function getEntity()
            {
                return $this->entity;
            }

            public function setEntity(Fieldable $entity)
            {
                $this->entity = $entity;
            }

            public function getLocale()
            {
                return null;
            }
        };

        $form = $this->container->get('unite.cms.fieldable_form_builder')->createForm($fieldable, $content);

        $this->assertInstanceOf(FieldableFormType::class, $form->getConfig()->getType()->getInnerType());
        $this->assertTrue($form->has('field1'));
        $this->assertEquals('Field 1', $form->get('field1')->getConfig()->getOption('label'));
        $this->assertEquals('Any Value', $form->get('field1')->getData());
    }

    public function testEmptyFormType()
    {
        $data = [];
        $options = ['fields' => []];
        $form = $this->container->get('form.factory')->create(FieldableFormType::class, $data, $options);

        $this->assertInstanceOf(FieldableFormType::class, $form->getConfig()->getType()->getInnerType());
        $this->assertCount(0, $form);
    }

    public function testFormTypeWithNestedFields()
    {

        $ft1 = $this->createMock(FieldTypeInterface::class);
        $ft1->expects($this->any())
            ->method('getType')
            ->willReturn('ft1');
        $ft1->expects($this->any())
            ->method('getIdentifier')
            ->willReturn('field1');
        $ft1->expects($this->any())
            ->method('getFormType')
            ->willReturn(TextType::class);

        $ft1Field = $this->createMock(FieldableField::class);

        $ft2 = $this->createMock(FieldTypeInterface::class);
        $ft2->expects($this->any())
            ->method('getType')
            ->willReturn('ft2');
        $ft2->expects($this->any())
            ->method('getIdentifier')
            ->willReturn('field2');
        $ft2->expects($this->any())
            ->method('getFormType')
            ->willReturn(TextType::class);

        $ft2Field = $this->createMock(FieldableField::class);

        $data = [
            'field1' => 'Just Text',
            'field2' => [
                ['title' => 'Row 1'],
                ['title' => 'Row 2'],
            ],
        ];
        $options = [
            'fields' => [
                new FieldableFormField($ft1, $ft1Field),
                new FieldableFormField($ft2, $ft2Field),
            ],
        ];

        $form = $this->container->get('form.factory')->create(FieldableFormType::class, $data, $options);

        $this->assertInstanceOf(FieldableFormType::class, $form->getConfig()->getType()->getInnerType());
        $this->assertCount(2, $form);
        $this->assertEquals($form->getData(), $data);

        $newData = [
            'field1' => 'A new value',
            'field2' => [['a' => 'b'], ['c' => 'd']],
        ];

        $form->submit(array_merge($newData, ['field3' => 'Does not exist']));
        $this->assertEquals($newData, $form->getData());

        // NOTE: Form validation is not handled by FieldTypes but the FormTypes, they return.
        // Since this are standard symfony form types, they must not be tested generally.
        // However you should test individual FormType implementations if you are using them in your FieldTypes.
    }
}

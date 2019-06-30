<?php

namespace UniteCMS\CoreBundle\Tests\Functional;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use UniteCMS\CoreBundle\Entity\Domain;
use UniteCMS\CoreBundle\Entity\Fieldable;
use UniteCMS\CoreBundle\Entity\FieldableContent;
use UniteCMS\CoreBundle\Entity\FieldableField;
use UniteCMS\CoreBundle\Entity\User;
use UniteCMS\CoreBundle\Field\FieldableValidation;
use UniteCMS\CoreBundle\Field\FieldTypeInterface;
use UniteCMS\CoreBundle\Form\FieldableFormBuilder;
use UniteCMS\CoreBundle\Form\FieldableFormField;
use UniteCMS\CoreBundle\Form\FieldableFormType;
use UniteCMS\CoreBundle\Form\LinkType;
use UniteCMS\CoreBundle\Security\Voter\FieldableFieldVoter;
use UniteCMS\CoreBundle\Tests\ContainerAwareTestCase;

class FieldableFormBuilderTest extends ContainerAwareTestCase
{
    public function setUp()
    {
        parent::setUp();
        $user = new User();
        $user->setRoles([User::ROLE_PLATFORM_ADMIN]);
        static::$container->get('security.token_storage')->setToken(new UsernamePasswordToken($user, '', 'main', $user->getRoles()));
    }

    public function testFormBuilderAvailable() {
        $this->assertTrue(static::$container->has('unite.cms.fieldable_form_builder'));
        $this->assertInstanceOf(FieldableFormBuilder::class, static::$container->get('unite.cms.fieldable_form_builder'));
    }

    public function testFormBuilderBuildForm() {

        $fieldable = new class implements Fieldable {
            public function getFields() {
                $a = new class implements FieldableField {
                    public $entity;
                    public function __toString() { return "test"; }
                    public function getEntity() { return $this->entity; }
                    public function setEntity($entity) { $this->entity = $entity; }
                    public function getType() { return 'text'; }
                    public function getIdentifier() { return 'field1'; }
                    public function getTitle() { return 'Field 1'; }
                    public function getSettings() { return []; }
                    public function getJsonExtractIdentifier() { return '$.' . $this->getIdentifier(); }
                    public function getPermissions(): array
                    {
                        return [
                            FieldableFieldVoter::LIST => 'true',
                            FieldableFieldVoter::VIEW => 'true',
                            FieldableFieldVoter::UPDATE => 'true',
                        ];
                    }
                    public function getIdentifierPath($delimiter = '/', $include_root = true)
                    {
                        $path = '';

                        if ($this->getEntity()) {
                            $path = $this->getEntity()->getIdentifierPath($delimiter, $include_root);
                        }

                        if(!empty($path)) {
                            $path .= $delimiter;
                        }

                        return $path.$this->getIdentifier();
                    }
                };
                $a->setEntity($this);
                return [$a];
            }
            public function setFields($fields) {}
            public function addField(FieldableField $field) {}
            public function getLocales(): array { return []; }
            public function getIdentifier() { return ''; }
            public function getIdentifierPath($delimiter = '/', $include_root = true) { return $include_root ? $this->getIdentifier() : ''; }
            public function getParentEntity() { return null; }
            public function getRootEntity(): Fieldable { return $this; }
            public function getValidations(): array { return []; }
            public function getDomain(){ return new Domain(); }

            /**
             * Finds a (possible) nested field in this fieldable by a path ("title", "blocks/0/title" etc.). If $reduce_path is
             * set to true, the fieldable should remove all resolved parts from the path.
             * @param $path
             * @param bool $reduce_path
             * @return mixed
             */
            public function resolveIdentifierPath(&$path, $reduce_path = false)
            {
                $parts = explode('/', $path);
                if(count($parts) < 0) {
                    return null;
                }

                $field_identifier = array_shift($parts);
                $field = $this->getFields()->get($field_identifier);

                if($reduce_path) {
                    $path = join('/', $parts);
                }

                return $field;
            }
        };
        $content = new class implements FieldableContent {
            private $data = ['field1' => 'Any Value'];
            public function setData(array $data) { $this->data = $data; }
            public function getData() : array { return $this->data; }
            public function getEntity() { return $this->entity; }
            public function setEntity(Fieldable $entity) { $this->entity = $entity; }
            public function getLocale() { return null; }
            public function setLocale($locale) { return $this; }
            public function isNew() : bool { return false; }
            public function getRootFieldableContent(): FieldableContent { return $this; }
            public function getId() { return 1; }
        };

        $form = static::$container->get('unite.cms.fieldable_form_builder')->createForm($fieldable, $content);

        $this->assertInstanceOf(FieldableFormType::class, $form->getConfig()->getType()->getInnerType());
        $this->assertTrue($form->has('field1'));
        $this->assertEquals('Field 1', $form->get('field1')->getConfig()->getOption('label'));
        $this->assertEquals('Any Value', $form->get('field1')->getData());
    }

    public function testEmptyFormType() {
        $data = [];
        $options = ['fields' => []];
        $form = static::$container->get('form.factory')->create(FieldableFormType::class, $data, $options);

        $this->assertInstanceOf(FieldableFormType::class, $form->getConfig()->getType()->getInnerType());
        $this->assertCount(0, $form);
    }

    public function testFormTypeWithNestedFields() {

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
            ->willReturn(LinkType::class);
        $ft2->expects($this->any())
            ->method('getFormOptions')
            ->willReturn(['title_widget' => true]);

        $ft2Field = $this->createMock(FieldableField::class);

        $data = [
            'field1' => 'Just Text',
            'field2' => [
                'url' => 'https://foo.com',
                'title' => 'Foo',
            ]
        ];
        $options = ['fields' => [
            new FieldableFormField($ft1, $ft1Field),
            new FieldableFormField($ft2, $ft2Field),
        ]];

        $form = static::$container->get('form.factory')->create(FieldableFormType::class, $data, $options);

        $this->assertInstanceOf(FieldableFormType::class, $form->getConfig()->getType()->getInnerType());
        $this->assertCount(2, $form);
        $this->assertEquals($form->getData(), $data);

        $newData = [
            'field1' => 'A new value',
            'field2' => [
                'url' => 'https://new.com',
                'title' => 'New',
            ],
        ];

        $form->submit(array_merge($newData, ['field3' => 'Does not exist']));
        $this->assertEquals($newData, $form->getData());

        // NOTE: Form validation is not handled by FieldTypes but the FormTypes, they return.
        // Since this are standard symfony form types, they must not be tested generally.
        // However you should test individual FormType implementations if you are using them in your FieldTypes.
    }
}

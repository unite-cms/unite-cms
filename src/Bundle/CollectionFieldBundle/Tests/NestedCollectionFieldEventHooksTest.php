<?php

namespace UniteCMS\CollectionFieldBundle\Tests\Field;

use Doctrine\ORM\EntityRepository;
use GraphQL\Type\Definition\Type;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use UniteCMS\CollectionFieldBundle\Field\Types\CollectionFieldType;
use UniteCMS\CollectionFieldBundle\Model\CollectionField;
use UniteCMS\CollectionFieldBundle\SchemaType\Factories\CollectionFieldTypeFactory;
use UniteCMS\CoreBundle\Entity\Content;
use UniteCMS\CoreBundle\Entity\ContentType;
use UniteCMS\CoreBundle\Entity\ContentTypeField;
use UniteCMS\CoreBundle\Field\FieldType;
use UniteCMS\CoreBundle\Field\FieldTypeInterface;
use UniteCMS\CoreBundle\Field\FieldTypeManager;

class NestedCollectionFieldEventHooksTest extends TestCase {

    /**
     * @var CollectionFieldType $collectionFieldType
     */
    private $collectionFieldType;

    /**
     * @var ContentTypeField $contentTypeField
     */
    private $contentTypeField;

    /**
     * @var FieldTypeInterface $nestedFieldType
     */
    private $nestedFieldType;

    /**
     * @var Content $content
     */
    private $content;

    /**
     * @var EntityRepository $repository
     */
    private $repository;

    public function setUp()
    {
        parent::setUp();

        $fieldTypeManager = $this->createMock(FieldTypeManager::class);
        $this->nestedFieldType = $this
            ->getMockBuilder(FieldType::class)
            ->setMethods(['onCreate', 'onUpdate', 'onSoftDelete', 'onHardDelete'])
            ->getMock();

        $fieldTypeManager->expects($this->any())->method('getFieldType')->willReturn($this->nestedFieldType);

        $this->repository = $this->createMock(EntityRepository::class);
        $this->collectionFieldType = new CollectionFieldType(
            $this->createMock(ValidatorInterface::class),
            $this->createMock(CollectionFieldTypeFactory::class),
            $fieldTypeManager
        );

        $this->contentTypeField = new ContentTypeField();
        $this->contentTypeField
            ->setIdentifier('rootCollection')
            ->setId(1)
            ->setTitle('Root Collection')
            ->setType(CollectionFieldType::TYPE)
            ->setEntity(new ContentType());

        // Create nested fields.
        $this->contentTypeField->getSettings()->fields = [
            new CollectionField('foo'),
        ];

        $this->content = new Content();
    }

    public function testCREATEHook()
    {
        // When adding two rows, nestedFieldType->onCreate should be called twice
        $this->nestedFieldType->expects($this->exactly(2))->method('onCreate');
        $this->nestedFieldType->expects($this->never())->method('onUpdate');
        $this->nestedFieldType->expects($this->never())->method('onSoftDelete');
        $this->nestedFieldType->expects($this->never())->method('onHardDelete');

        $data = [
            'rootCollection' => [ [], [], ]
        ];
        $this->collectionFieldType->onCreate($this->contentTypeField, $this->content, $this->repository, $data);
    }
}
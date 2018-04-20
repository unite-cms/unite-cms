<?php

namespace UniteCMS\CollectionFieldBundle\Tests\Field;

use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
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

    /**
     * Helper method to create parameter array for nested event hook method calls.
     * @param array|null $data1
     * @param array|null $data2
     * @return array
     */
    private function hParam(array $data1 = null, array $data2 = null) : array {
        $params = [
            $this->contentTypeField->getSettings()->fields[0],
            $this->content,
            $this->repository,
        ];

        if($data1) {
            array_push($params, $data1);
        }

        if($data2) {
            array_push($params, $data2);
        }

        return $params;
    }

    /**
     * Test that a CREATE hook get passed to a child field.
     */
    public function testCREATEHook()
    {
        // When adding two rows, nestedFieldType->onCreate should be called twice
        $this->nestedFieldType->expects($this->exactly(2))->method('onCreate')->withConsecutive(
            $this->hParam(['foo' => 'value']),
            $this->hParam(['baa' => 'value'])
        );

        $this->nestedFieldType->expects($this->never())->method('onUpdate');
        $this->nestedFieldType->expects($this->never())->method('onSoftDelete');
        $this->nestedFieldType->expects($this->never())->method('onHardDelete');

        $data = ['rootCollection' => [ ['foo' => 'value'], ['baa' => 'value'], ]];

        $this->collectionFieldType->onCreate($this->contentTypeField, $this->content, $this->repository, $data);
    }

    /**
     * Test that a UPDATE hook get passed to a child field. Here we have three cases:
     *   1. A row was added => should trigger an CREATE
     *   2. A row was updated => should trigger an UPDATE
     *   3. A row was deleted => should trigger an HARD DELETE
     */
    public function testUPDATECreateHook()
    {
        // When adding a new row, nestedFieldType->onCreate should be called
        $this->nestedFieldType->expects($this->exactly(1))->method('onCreate')->withConsecutive(
            $this->hParam([], ['foo' => 'value'])
        );

        $this->nestedFieldType->expects($this->never())->method('onUpdate');
        $this->nestedFieldType->expects($this->never())->method('onSoftDelete');
        $this->nestedFieldType->expects($this->never())->method('onHardDelete');

        $old_data = ['rootCollection' => []];
        $data = ['rootCollection' => [['foo' => 'value']]];

        $this->collectionFieldType->onUpdate(
            $this->contentTypeField,
            $this->content,
            $this->repository,
            $old_data,
            $data
        );

    }

    public function testUPDATEUpdateHook()
    {
        // When updating a row, nestedFieldType->onUpdate should be called
        $this->nestedFieldType->expects($this->never())->method('onCreate');

        $this->nestedFieldType->expects($this->exactly(2))->method('onUpdate')->withConsecutive(
            $this->hParam(['foo' => 'old_value'], ['foo' => 'new_value']),
            $this->hParam(['baa' => 'old_value'], ['baa' => 'new_value'])
        );

        $this->nestedFieldType->expects($this->never())->method('onSoftDelete');
        $this->nestedFieldType->expects($this->never())->method('onHardDelete');

        $old_data = ['rootCollection' => [['foo' => 'old_value'], ['baa' => 'old_value']]];
        $data = ['rootCollection' => [['foo' => 'new_value'], ['baa' => 'new_value']]];

        $this->collectionFieldType->onUpdate(
            $this->contentTypeField,
            $this->content,
            $this->repository,
            $old_data,
            $data
        );
    }

    public function testUPDATEDeleteHook()
    {
        // When deleting a row, nestedFieldType->onDelete should be called
        $this->nestedFieldType->expects($this->never())->method('onCreate');
        $this->nestedFieldType->expects($this->never())->method('onUpdate');
        $this->nestedFieldType->expects($this->never())->method('onSoftDelete');
        $this->nestedFieldType->expects($this->exactly(2))->method('onHardDelete')->withConsecutive(
            $this->hParam(['foo' => 'old_value']),
            $this->hParam(['baa' => 'old_value'])
        );

        $old_data = ['rootCollection' => [ ['foo' => 'old_value'], ['baa' => 'old_value'] ]];
        $data = ['rootCollection' => [ ]];

        $this->collectionFieldType->onUpdate($this->contentTypeField, $this->content, $this->repository, $old_data, $data);
    }

    public function testUPDATEDeleteElementFromTheMiddleHook()
    {
        // When deleting a row from the middle of the rows array, nestedFieldType->onDelete should be called only for this one
        $this->nestedFieldType->expects($this->never())->method('onCreate');
        $this->nestedFieldType->expects($this->never())->method('onUpdate');
        $this->nestedFieldType->expects($this->never())->method('onSoftDelete');
        $this->nestedFieldType->expects($this->exactly(1))->method('onHardDelete')->withConsecutive(
            $this->hParam(['baa' => 'old_value'])
        );

        $old_data = ['rootCollection' => [ 0 => ['foo' => 'old_value'], 1 => ['baa' => 'old_value'], 2 => ['luu' => 'old_value'] ]];
        $data = ['rootCollection' => [ 0 => ['foo' => 'old_value'], 2 => ['luu' => 'old_value'] ]];

        $this->collectionFieldType->onUpdate($this->contentTypeField, $this->content, $this->repository, $old_data, $data);

        // Array indexes should get normalized by onUpdate.
        $this->assertEquals([0, 1], array_keys($data['rootCollection']));
    }

    public function testUPDATEMixedHook()
    {
        // When deleting a row from the middle of the rows array, nestedFieldType->onDelete should be called only for this one
        $this->nestedFieldType->expects($this->exactly(1))->method('onCreate')->withConsecutive($this->hParam(['row3' => 'value']));
        $this->nestedFieldType->expects($this->exactly(1))->method('onUpdate')->withConsecutive($this->hParam(['foo' => 'baa'], ['foo' => 'new_value']));
        $this->nestedFieldType->expects($this->never())->method('onSoftDelete');
        $this->nestedFieldType->expects($this->exactly(1))->method('onHardDelete')->withConsecutive($this->hParam(['luu' => 'old_value']));

        $old_data = ['rootCollection' => [
            0 => ['foo' => 'baa'],
            1 => ['baa' => 'old_value'],
            2 => ['luu' => 'old_value'],
        ]];
        $data = ['rootCollection' => [
            0 => ['foo' => 'new_value'],         // Update
            1 => ['baa' => 'old_value'],         // Stay the same
                                                 // Delete
            3 => ['row3' => 'value'],            // Create
        ]];

        $this->collectionFieldType->onUpdate($this->contentTypeField, $this->content, $this->repository, $old_data, $data);

        // Array indexes should get normalized by onUpdate.
        $this->assertEquals([0, 1, 2], array_keys($data['rootCollection']));
    }

    public function testSOFTDELETEHook()
    {
        // When deleting a row, nestedFieldType->onDelete should be called
        $this->nestedFieldType->expects($this->never())->method('onCreate');
        $this->nestedFieldType->expects($this->never())->method('onUpdate');
        $this->nestedFieldType->expects($this->exactly(1))->method('onSoftDelete')->withConsecutive(
            $this->hParam(['foo' => 'old_value'])
        );
        $this->nestedFieldType->expects($this->never())->method('onHardDelete');

        $old_data = ['rootCollection' => [ ['foo' => 'old_value'] ]];

        $this->collectionFieldType->onSoftDelete($this->contentTypeField, $this->content, $this->repository, $old_data);
    }

    public function testHARDDELETEHook()
    {
        // When deleting a row, nestedFieldType->onDelete should be called
        $this->nestedFieldType->expects($this->never())->method('onCreate');
        $this->nestedFieldType->expects($this->never())->method('onUpdate');
        $this->nestedFieldType->expects($this->never())->method('onSoftDelete');
        $this->nestedFieldType->expects($this->exactly(1))->method('onHardDelete')->withConsecutive(
            $this->hParam(['foo' => 'old_value'])
        );

        $old_data = ['rootCollection' => [ ['foo' => 'old_value'] ]];

        $this->collectionFieldType->onHardDelete($this->contentTypeField, $this->content, $this->repository, $old_data);
    }
}
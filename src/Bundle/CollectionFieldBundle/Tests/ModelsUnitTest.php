<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 24.01.18
 * Time: 08:38
 */

namespace UniteCMS\CollectionFieldBundle\Tests;

use PHPUnit\Framework\TestCase;
use UniteCMS\CollectionFieldBundle\Model\Collection;
use UniteCMS\CollectionFieldBundle\Model\CollectionField;
use UniteCMS\CoreBundle\Field\FieldableFieldSettings;

class ModelsUnitTest extends TestCase {

  public function testModelsGetterAndSetter() {

    $f1 = [];
    $f2 = ['identifier' => 'f2'];
    $f3 = ['identifier' => 'f3', 'title' => 'F3'];
    $f4 = ['identifier' => 'f4', 'title' => 'F4', 'type' => 'foo'];
    $f5 = ['identifier' => 'f5', 'title' => 'F5', 'type' => 'baa', 'settings' => ['foo' => 'baa', 'baa' => 'foo']];
    $f6 = ['identifier' => 'f2', 'title' => 'F6'];

    $collection1 = new Collection([], '');
    $collection2 = new Collection([], 'c2');
    $collection3 = new Collection([$f1, $f2, $f3, $f4, $f5, $f6], 'c3');

    $this->assertEquals('', $collection1->getIdentifier());
    $this->assertEquals([], $collection1->getLocales());

    $this->assertEquals('c2', $collection2->getIdentifier());
    $this->assertEquals([], $collection1->getFields()->toArray());
    $this->assertEquals([], $collection2->getFields()->toArray());

    // Since two fields have the same identifier, only the second one got registered.
    $this->assertCount(5, $collection3->getFields());

    $this->assertNull($collection3->getFields()->get('f1'));
    $this->assertEquals('', (string)$collection3->getFields()->get('f2'));
    $this->assertEquals('F3', (string)$collection3->getFields()->get('f3'));
    $this->assertEquals('F4', (string)$collection3->getFields()->get('f4'));
    $this->assertEquals('F5', (string)$collection3->getFields()->get('f5'));

    $this->assertEquals($collection3, $collection3->getFields()->get('f2')->getEntity());
    $this->assertEquals(new FieldableFieldSettings(['foo' => 'baa', 'baa' => 'foo']), $collection3->getFields()->get('f5')->getSettings());
  }
}

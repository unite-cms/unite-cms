<?php

namespace UniteCMS\CoreBundle\Tests\Field;


use UniteCMS\CoreBundle\Entity\Content;
use UniteCMS\CoreBundle\Entity\ContentType;
use UniteCMS\CoreBundle\Entity\ContentTypeField;
use UniteCMS\CoreBundle\Entity\Domain;
use UniteCMS\CoreBundle\Entity\Organization;
use UniteCMS\CoreBundle\Field\FieldableFieldSettings;

class SortIndexFieldTypeTest extends FieldTypeTestCase
{
    public function testContentTypeFieldTypeWithEmptySettings() {

        // Empty settings can be valid.
        $ctField = $this->createContentTypeField('sortindex');
        $this->assertCount(0, static::$container->get('validator')->validate($ctField));
    }

    public function testSortIndexFieldTypeWithInvalidSettings()
    {
        // Sort Index Field with invalid settings should not be valid.
        $ctField = $this->createContentTypeField('sortindex');
        $ctField->setSettings(new FieldableFieldSettings(
            [
                'hidden' => 123,
                'foo' => 'baa'
            ]
        ));

        $errors = static::$container->get('validator')->validate($ctField);
        $this->assertCount(2, $errors);
        $this->assertEquals('additional_data', $errors->get(0)->getMessageTemplate());
        $this->assertEquals('noboolean_value', $errors->get(1)->getMessageTemplate());
    }

    public function testSortIndexFieldTypeWithValidSettings()
    {
        // Phone Type Field with invalid settings should not be valid.
        $ctField = $this->createContentTypeField('sortindex');
        $ctField->setSettings(new FieldableFieldSettings(
            [
                'description' => 'my description',
                'hidden' => true
            ]
        ));

        $errors = static::$container->get('validator')->validate($ctField);
        $this->assertCount(0, $errors);
    }


    public function testAutoUpdateSortIndexOnInsertUpdateDelete() {

        $contentType = new ContentType();
        $contentType->setTitle('ct')
            ->setIdentifier('ct')
            ->setDomain(new Domain())
            ->getDomain()
            ->setTitle('D1')
            ->setIdentifier('d1')
            ->setOrganization(new Organization())
            ->getOrganization()
            ->setTitle('O1')
            ->setIdentifier('o1');

        $field = new ContentTypeField();
        $field->setType('sortindex')->setIdentifier('position')->setTitle('Position');
        $contentType->addField($field);

        $field = new ContentTypeField();
        $field->setType('text')->setIdentifier('label')->setTitle('Label');
        $contentType->addField($field);

        $this->em->persist($contentType->getDomain()->getOrganization());
        $this->em->persist($contentType->getDomain());
        $this->em->persist($contentType);
        $this->em->flush();

        // Create content for this content type.
        $content = [];
        for($i = 0; $i < 4; $i++) {
            $content['C' . ($i + 1)] = new Content();
            $content['C' . ($i + 1)]
                ->setData(['position' => 0, 'label' => 'C' . ($i + 1)])
                ->setContentType($contentType);
            $this->em->persist($content['C' . ($i + 1)]);
            $this->em->flush();
        }

        // Make sure, that content got an auto incremented position.
        $getContent = $this->em->getRepository('UniteCMSCoreBundle:Content')->createQueryBuilder('c')
            ->select('c')
            ->where('c.contentType = :contentType')
            ->orderBy("JSON_EXTRACT(c.data, '$.position')", 'ASC')
            ->getQuery()->execute([':contentType' => $contentType]);

        $this->assertEquals(0, $getContent[0]->getData()['position']);
        $this->assertEquals(1, $getContent[1]->getData()['position']);
        $this->assertEquals(2, $getContent[2]->getData()['position']);
        $this->assertEquals(3, $getContent[3]->getData()['position']);

        // Now move the first element to the last position.
        $data = $content['C1']->getData();
        $data['position'] = 3;
        $content['C1']->setData($data);
        $this->em->flush($content['C1']);
        $this->em->clear();

        // Make sure, that the content is in correct order.
        $getContent = $this->em->getRepository('UniteCMSCoreBundle:Content')->createQueryBuilder('c')
            ->select('c')
            ->where('c.contentType = :contentType')->setParameter(':contentType', $contentType)
            ->addOrderBy("JSON_EXTRACT(c.data, '$.position')")
            ->getQuery()->execute();

        $this->assertEquals(['position' => 0, 'label' => 'C2'], $getContent[0]->getData());
        $this->assertEquals(['position' => 1, 'label' => 'C3'], $getContent[1]->getData());
        $this->assertEquals(['position' => 2, 'label' => 'C4'], $getContent[2]->getData());
        $this->assertEquals(['position' => 3, 'label' => 'C1'], $getContent[3]->getData());


        // Now move the 3rd element to the first position.
        $getContent[1]->setData(['position' => 0, 'label' => 'C3']);
        $this->em->flush($getContent[1]);
        $this->em->clear();

        // Make sure, that the content is in correct order.
        $getContent = $this->em->getRepository('UniteCMSCoreBundle:Content')->createQueryBuilder('c')
            ->select('c')
            ->where('c.contentType = :contentType')->setParameter(':contentType', $contentType)
            ->addOrderBy("JSON_EXTRACT(c.data, '$.position')")
            ->getQuery()->execute();

        $this->assertEquals(['position' => 0, 'label' => 'C3'], $getContent[0]->getData());
        $this->assertEquals(['position' => 1, 'label' => 'C2'], $getContent[1]->getData());
        $this->assertEquals(['position' => 2, 'label' => 'C4'], $getContent[2]->getData());
        $this->assertEquals(['position' => 3, 'label' => 'C1'], $getContent[3]->getData());

        // Now move C1 to position 1.
        $getContent[3]->setData(['position' => 1, 'label' => 'C1']);
        $this->em->flush($getContent[3]);
        $this->em->clear();

        // Make sure, that the content is in correct order.
        $getContent = $this->em->getRepository('UniteCMSCoreBundle:Content')->createQueryBuilder('c')
            ->select('c')
            ->where('c.contentType = :contentType')->setParameter(':contentType', $contentType)
            ->addOrderBy("JSON_EXTRACT(c.data, '$.position')")
            ->getQuery()->execute();

        $this->assertEquals(['position' => 0, 'label' => 'C3'], $getContent[0]->getData());
        $this->assertEquals(['position' => 1, 'label' => 'C1'], $getContent[1]->getData());
        $this->assertEquals(['position' => 2, 'label' => 'C2'], $getContent[2]->getData());
        $this->assertEquals(['position' => 3, 'label' => 'C4'], $getContent[3]->getData());

        // Now delete one content element, all elements after that element should auto update.
        $deletedContentId = $getContent[1]->getId();
        $this->em->remove($getContent[1]);
        $this->em->flush($getContent[1]);
        $this->em->clear();

        $getContent = $this->em->getRepository('UniteCMSCoreBundle:Content')->createQueryBuilder('c')
            ->select('c')
            ->where('c.contentType = :contentType')->setParameter(':contentType', $contentType)
            ->addOrderBy("JSON_EXTRACT(c.data, '$.position')")
            ->getQuery()->execute();

        $this->assertEquals(['position' => 0, 'label' => 'C3'], $getContent[0]->getData());
        $this->assertEquals(['position' => 1, 'label' => 'C2'], $getContent[1]->getData());
        $this->assertEquals(['position' => 2, 'label' => 'C4'], $getContent[2]->getData());

        // Now restore the content element, it should get it's old position.
        $this->em->getFilters()->disable('gedmo_softdeleteable');
        $deletedContent = $this->em->getRepository('UniteCMSCoreBundle:Content')->findOneBy([
            'contentType' => $contentType,
            'id' => $deletedContentId,
        ]);
        $this->em->getFilters()->enable('gedmo_softdeleteable');
        $deletedContent->recoverDeleted();
        $this->em->flush();
        $this->em->clear();

        // Make sure, that the content is in correct order.
        $getContent = $this->em->getRepository('UniteCMSCoreBundle:Content')->createQueryBuilder('c')
            ->select('c')
            ->where('c.contentType = :contentType')->setParameter(':contentType', $contentType)
            ->addOrderBy("JSON_EXTRACT(c.data, '$.position')")
            ->getQuery()->execute();

        $this->assertEquals(['position' => 0, 'label' => 'C3'], $getContent[0]->getData());
        $this->assertEquals(['position' => 1, 'label' => 'C1'], $getContent[1]->getData());
        $this->assertEquals(['position' => 2, 'label' => 'C2'], $getContent[2]->getData());
        $this->assertEquals(['position' => 3, 'label' => 'C4'], $getContent[3]->getData());
    }
}

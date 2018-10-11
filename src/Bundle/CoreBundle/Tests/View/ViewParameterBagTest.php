<?php

namespace UniteCMS\CoreBundle\Tests\View;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RequestContext;
use UniteCMS\CoreBundle\Field\FieldTypeManager;
use UniteCMS\CoreBundle\View\ViewParameterBag;
use UniteCMS\CoreBundle\View\ViewTypeInterface;
use UniteCMS\CoreBundle\Entity\View;
use UniteCMS\CoreBundle\Entity\ContentType;
use UniteCMS\CoreBundle\Entity\Domain;
use UniteCMS\CoreBundle\Entity\Organization;

class ViewParameterBagTest extends TestCase
{

    public function testGetterAndSetter()
    {
        $bag = new ViewParameterBag();
        $this->assertEquals(
            ['foo' => 'baa', 'foo2' => ['baa']],
            $bag->setSettings(['foo' => 'baa', 'foo2' => ['baa']])->getSettings()
        );
        $this->assertEquals('baa', $bag->get('foo'));
        $this->assertEquals(['baa'], $bag->get('foo2'));
        $this->assertNull($bag->get('any_unknown'));

        $this->assertEquals('foo_token', $bag->setCsrfToken('foo_token')->getCsrfToken());

        $this->assertEquals('any_select_mode', $bag->setSelectMode('any_select_mode')->getSelectMode());
        $this->assertEquals('any_url_pattern', $bag->setUpdateUrlPattern('any_url_pattern')->getUpdateUrlPattern());
        $this->assertEquals(
            'any_endpoint_pattern',
            $bag->setApiEndpointPattern('any_endpoint_pattern')->getApiEndpointPattern()
        );
        $this->assertEquals(
            'any_create_pattern',
            $bag->setCreateUrlPattern('any_create_pattern')->getCreateUrlPattern()
        );
        $this->assertEquals(
            'any_delete_pattern',
            $bag->setDeleteUrlPattern('any_delete_pattern')->getDeleteUrlPattern()
        );
        $this->assertEquals(
            'any_recover_pattern',
            $bag->setRecoverUrlPattern('any_recover_pattern')->getRecoverUrlPattern()
        );
        $this->assertEquals(
            'any_delete_definitely_pattern',
            $bag->setDeleteDefinitelyUrlPattern('any_delete_definitely_pattern')->getDeleteDefinitelyUrlPattern()
        );
        $this->assertEquals(
            'any_translations_pattern',
            $bag->setTranslationsUrlPattern('any_translations_pattern')->getTranslationsUrlPattern()
        );
        $this->assertEquals(
            'any_revisions_pattern',
            $bag->setRevisionsUrlPattern('any_revisions_pattern')->getRevisionsUrlPattern()
        );

        $this->assertEquals('any_url_pattern', $bag->getUrl('update'));
        $this->assertEquals('any_endpoint_pattern', $bag->getUrl('api'));
        $this->assertEquals('any_create_pattern', $bag->getUrl('create'));
        $this->assertEquals('any_delete_pattern', $bag->getUrl('delete'));
        $this->assertEquals('any_recover_pattern', $bag->getUrl('recover'));
        $this->assertEquals('any_delete_definitely_pattern', $bag->getUrl('delete_definitely'));
        $this->assertEquals('any_translations_pattern', $bag->getUrl('translations'));
        $this->assertEquals('any_revisions_pattern', $bag->getUrl('revisions'));
        $this->assertNull($bag->getUrl('any_unknown'));

        $this->assertEquals(
            json_encode(
                [
                    'title' => '',
                    'subTitle' => '',
                    'fields' => [],
                    'urls' => [
                        'api' => 'any_endpoint_pattern',
                        'create' => 'any_create_pattern',
                        'update' => 'any_url_pattern',
                        'delete' => 'any_delete_pattern',
                        'recover' => 'any_recover_pattern',
                        'delete_definitely' => 'any_delete_definitely_pattern',
                        'translations' => 'any_translations_pattern',
                        'revisions' => 'any_revisions_pattern',
                    ],
                    'select' => [
                        'mode' => 'any_select_mode',
                        'is_mode_none' => false,
                        'is_mode_single' => false,
                    ],
                    'csrf_token' => 'foo_token',
                    'settings' => [
                        'foo' => 'baa',
                        'foo2' => ['baa'],
                    ],
                ]
            ),
            json_encode($bag)
        );
    }

    public function testSelectModeIndicator()
    {
        $bag = new ViewParameterBag();
        $bag->setSelectMode(ViewTypeInterface::SELECT_MODE_SINGLE);
        $this->assertTrue($bag->isSelectModeSingle());
        $this->assertFalse($bag->isSelectModeNone());

        $bag->setSelectMode(ViewTypeInterface::SELECT_MODE_NONE);
        $this->assertFalse($bag->isSelectModeSingle());
        $this->assertTrue($bag->isSelectModeNone());
    }

    public function testCreateFromView()
    {

        $view = new View();
        $view->setIdentifier('co1')->setTitle('Baa')->setContentType(new ContentType())->getContentType()
            ->setIdentifier('ct1')->setTitle('Foo')->setLocales(['de', 'en'])->setDomain(new Domain())->getDomain()
            ->setIdentifier('d1')->setOrganization(new Organization())->getOrganization()
            ->setIdentifier('o1');
        $generator = new Class implements UrlGeneratorInterface
        {
            public function setContext(RequestContext $context)
            {
            }

            public function getContext()
            {
            }

            public function generate($name, $parameters = array(), $referenceType = self::ABSOLUTE_PATH)
            {
                return $name.'_'.implode(',', $parameters);
            }
        };
        $this->assertEquals(
            json_encode(
                [
                    'title' => 'Foo',
                    'subTitle' => 'Baa',
                    'fields' => [],
                    'urls' => [
                        'api' => 'unitecms_core_api_d1,o1',
                        'create' => 'unitecms_core_content_create_d1,o1,co1,ct1',
                        'update' => 'unitecms_core_content_update_d1,o1,co1,ct1,__id__',
                        'delete' => 'unitecms_core_content_delete_d1,o1,co1,ct1,__id__',
                        'recover' => 'unitecms_core_content_recover_d1,o1,co1,ct1,__id__',
                        'delete_definitely' => 'unitecms_core_content_deletedefinitely_d1,o1,co1,ct1,__id__',
                        'translations' => 'unitecms_core_content_translations_d1,o1,co1,ct1,__id__',
                        'revisions' => 'unitecms_core_content_revisions_d1,o1,co1,ct1,__id__',
                    ],
                    'select' => [
                        'mode' => ViewTypeInterface::SELECT_MODE_SINGLE,
                        'is_mode_none' => false,
                        'is_mode_single' => true,
                    ],
                    'csrf_token' => '',
                    'settings' => [
                        'foo' => 'baa',
                    ],
                ]
            ),
            json_encode(
                ViewParameterBag::createFromView(
                    $view,
                    $generator,
                    ViewTypeInterface::SELECT_MODE_SINGLE,
                    ['foo' => 'baa']
                )
            )
        );
    }
}

<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 26.04.18
 * Time: 08:32
 */

namespace UniteCMS\WysiwygFieldBundle\Tests;

use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Test\TypeTestCase;
use UniteCMS\WysiwygFieldBundle\Form\WysiwygType;

class WysiwygTypeTest extends TypeTestCase
{
    public function testFormXssCleanBeforeSave() {

        $string_with_xss = '<a href="https://example.com"><script>alert("XSS");</script></a>';


        $data = new class { public $wysiwyg; };
        $form = $this->factory->createBuilder(FormType::class, $data)
            ->add('wysiwyg', WysiwygType::class)
            ->getForm();

        $form->submit(['wysiwyg' => $string_with_xss]);
        $this->assertEquals('<a href="https://example.com">alert&#40;"XSS"&#41;;</a>', $data->wysiwyg);
    }

    public function testFormXssAllowInlineStyles() {

        $string_with_inline_styles = '<p style="text-align: center;">Foo</p>';


        $data = new class { public $wysiwyg; };
        $form = $this->factory->createBuilder(FormType::class, $data)
            ->add('wysiwyg', WysiwygType::class)
            ->getForm();

        $form->submit(['wysiwyg' => $string_with_inline_styles]);
        $this->assertEquals($string_with_inline_styles, $data->wysiwyg);
    }
}

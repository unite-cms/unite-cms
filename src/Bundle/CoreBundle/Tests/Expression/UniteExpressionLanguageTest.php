<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 17.05.18
 * Time: 16:43
 */

namespace UniteCMS\CoreBundle\Tests\Expression;

use PHPUnit\Framework\TestCase;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use UniteCMS\CoreBundle\Expression\UniteExpressionLanguage;

define('TEST_PLAIN_EXPRESSION_LANGUAGE_TEST_CONSTANT', 'foo');

class UniteExpressionLanguageTest extends TestCase
{

    private $expression;

    public function setUp()
    {
        $this->expression = 'constant("TEST_PLAIN_EXPRESSION_LANGUAGE_TEST_CONSTANT")';
    }

    public function testConstantAvailable() {
        $lang = new ExpressionLanguage();
        $this->assertEquals('foo', $lang->evaluate($this->expression));
    }

    /**
     * @expectedException \Symfony\Component\ExpressionLanguage\SyntaxError
     */
    public function testConstantNotAvailable() {
        $plainLang = new UniteExpressionLanguage();
        $this->assertNull($plainLang->evaluate($this->expression));
    }

    public function testSlugFunction() {
        $plainLang = new UniteExpressionLanguage();
        $this->assertEquals(23, eval('return ' . $plainLang->compile('slug(23)') . ';'));
        $this->assertEquals('foo', eval('return ' . $plainLang->compile('slug("foo")') . ';'));
        $this->assertEquals('foo', $plainLang->evaluate('slug("foo")'));
        $this->assertEquals('baa', $plainLang->evaluate('slug("BaA")'));
        $this->assertEquals('a-b-c-', $plainLang->evaluate('slug("A B C!")'));
        $this->assertEquals('this-is-a-test---', $plainLang->evaluate('slug("This is a Test !-ö")'));
    }
}
<?php


namespace UniteCMS\CoreBundle\Security\Expression;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Validator\Constraints as Assert;

class ExpressionLanguageOverrideTest extends KernelTestCase
{
    public function setUp() {
        static::bootKernel();
        static::$container->get('security.token_storage')->setToken(new AnonymousToken('', ''));
    }

    /**
     * @expectedException \Symfony\Component\ExpressionLanguage\SyntaxError
     * @expectedExceptionMessage The function "constant" does not exist around position 1 for expression `constant(APP_ENV) == "test"`.
     */
    public function testMissingConstOnValidationExpression(){
        static::$container
            ->get('validator')
            ->validate('test', [
                new Assert\Expression('constant(APP_ENV) == "test"'),
            ]);
    }

    /**
     * @expectedException \Symfony\Component\ExpressionLanguage\SyntaxError
     * @expectedExceptionMessage The function "constant" does not exist around position 1 for expression `constant(APP_ENV) == "test"`.
     */
    public function testMissingConstOnAuthorizationExpression(){
        static::$container
            ->get('security.authorization_checker')
            ->isGranted(new Expression('constant(APP_ENV) == "test"'));
    }
}

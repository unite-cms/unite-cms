<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 14.09.18
 * Time: 11:01
 */

namespace UniteCMS\CoreBundle\Tests\Exception;

use GraphQL\Error\FormattedError;
use GraphQL\Error\UserError;
use PHPUnit\Framework\TestCase;
use GraphQL\Error\Error;
use UniteCMS\CoreBundle\Exception\UserErrorAtPath;

class UserErrorAtPathFormatterTest extends TestCase
{
    public function testFormatUserErrorAtPath() {

        $error = new Error('This is my message', ['foo', 'baa']);

        // If no previous error is set, user error at formatter should to the same as default formatter.
        $this->assertEquals(FormattedError::createFromException($error), UserErrorAtPath::createFormattedErrorFromException($error));

        // If previous error is not a UserErrorAtPath, user error at formatter should to the same as default formatter.
        $error = new Error('This is my message', ['foo', 'baa'], null, null, null, new UserError('Prev'));
        $this->assertEquals(FormattedError::createFromException($error), UserErrorAtPath::createFormattedErrorFromException($error));

        // If previous error is a UserErrorAtPath, path and node should get overridden.
        $prev = new UserErrorAtPath('Prev message', ['prev_foo', 'prev_baa'], 'custom category');
        $error = new Error('This is my message', null, null, null, null, $prev);
        $formattedError = UserErrorAtPath::createFormattedErrorFromException($error);
        $this->assertEquals([
            'message' => 'This is my message',
            'category' => 'custom category',
            'path' => ['prev_foo', 'prev_baa'],
        ], $formattedError);

    }
}
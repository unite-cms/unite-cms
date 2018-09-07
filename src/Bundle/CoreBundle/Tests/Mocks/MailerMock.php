<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 07.09.18
 * Time: 15:22
 */

namespace UniteCMS\CoreBundle\Tests\Mocks;

class MailerMock extends \Swift_Mailer {
    /**
     * @var \Swift_Message[] $messages
     */
    public $messages = [];

    public function __construct(){

    }

    public function send(\Swift_Mime_SimpleMessage $message, &$failedRecipients = null) {
        $this->messages[] = $message;
    }
};
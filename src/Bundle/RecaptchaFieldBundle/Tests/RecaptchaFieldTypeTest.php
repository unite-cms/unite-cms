<?php

namespace UniteCMS\RecaptchaFieldBundle\Tests;

use ReCaptcha\ReCaptcha;
use ReCaptcha\RequestMethod;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Context\ExecutionContext;
use Symfony\Component\Validator\Tests\Fixtures\ConstraintA;
use UniteCMS\CoreBundle\Entity\Content;
use UniteCMS\CoreBundle\Entity\ContentTypeField;
use UniteCMS\CoreBundle\Entity\FieldableField;
use UniteCMS\CoreBundle\Field\FieldableFieldSettings;
use UniteCMS\CoreBundle\Tests\Field\FieldTypeTestCase;
use UniteCMS\RecaptchaFieldBundle\Field\Types\RecaptchaFieldType;

class RecaptchaFieldTypeTest extends FieldTypeTestCase
{

    protected function getMockRecaptchaFieldType($responseJson = '', $pathInfo = 'https://foo/baa/api', $clientIp = '127.0.0.1') : RecaptchaFieldType
    {
        $method = $this->getMockBuilder(RequestMethod::class)
            ->disableOriginalConstructor()
            ->setMethods(array('submit'))
            ->getMock();
        $method->expects($this->any())
            ->method('submit')
            ->with($this->callback(function ($params) {
                return true;
            }))
            ->will($this->returnValue($responseJson));

        $requet = new Request();
        $requet->server->set('REQUEST_URI', $pathInfo);
        $requet->server->set('REMOTE_ADDR', [$clientIp]);
        $requestStack = new RequestStack();
        $requestStack->push($requet);
        return new RecaptchaFieldType($requestStack, $method);
    }

    protected function assertRecaptchaResponse(FieldableField $field, $data, string $mockedResponse, $api = true) : ConstraintViolationListInterface {
        $type = $this->getMockRecaptchaFieldType($mockedResponse, $api ? 'https://foo/baa/api' : 'content/pages/all/create');
        $context = new ExecutionContext(static::$container->get('validator'), new Content(), static::$container->get('translator'));
        $context->setConstraint(new ConstraintA());
        $type->validateData($field, $data, $context);
        return $context->getViolations();
    }

    public function testFieldSettings()
    {
        // Empty is not allowed
        $field = $this->createContentTypeField('recaptcha');
        $errors = static::$container->get('validator')->validate($field);
        $this->assertCount(1, $errors);
        $this->assertEquals('settings.secret_key', $errors->get(0)->getPropertyPath());
        $this->assertEquals('required', $errors->get(0)->getMessageTemplate());

        $field->setSettings(new FieldableFieldSettings([
            'secret_key' => [],
            'expected_hostname' => false,
            'expected_apk_package_name' => 34,
            'expected_action' => [],
            'score_threshold' => 'ABC',
            'challenge_timeout' => 'ABC',
        ]));
        $errors = static::$container->get('validator')->validate($field);
        $this->assertCount(7, $errors);
        $this->assertEquals('nostring_value', $errors->get(0)->getMessageTemplate());
        $this->assertEquals('nostring_value', $errors->get(1)->getMessageTemplate());
        $this->assertEquals('nostring_value', $errors->get(2)->getMessageTemplate());
        $this->assertEquals('nostring_value', $errors->get(3)->getMessageTemplate());
        $this->assertEquals('This value should be of type {{ type }}.', $errors->get(4)->getMessageTemplate());
        $this->assertEquals('This value should be a valid number.', $errors->get(5)->getMessageTemplate());
        $this->assertEquals('This value should be of type {{ type }}.', $errors->get(6)->getMessageTemplate());

        $field->setSettings(new FieldableFieldSettings([
            'secret_key' => 'foo',
            'expected_apk_package_name' => 'xxx',
            'expected_action' => 'any',
            'score_threshold' => 5,
            'challenge_timeout' => 5.5,
        ]));
        $errors = static::$container->get('validator')->validate($field);
        $this->assertCount(3, $errors);
        $this->assertEquals('This value should be of type {{ type }}.', $errors->get(0)->getMessageTemplate());
        $this->assertEquals('This value should be {{ limit }} or less.', $errors->get(1)->getMessageTemplate());
        $this->assertEquals('This value should be of type {{ type }}.', $errors->get(2)->getMessageTemplate());

        $field->setSettings(new FieldableFieldSettings([
            'secret_key' => 'foo',
            'expected_hostname' => 'baa.com',
            'score_threshold' => 1.0,
            'challenge_timeout' => 300,
        ]));
        $this->assertCount(0, static::$container->get('validator')->validate($field));

        $field->setSettings(new FieldableFieldSettings([
            'secret_key' => 'foo',
            'expected_hostname' => 'http://baa.com',
            'score_threshold' => 0.5,
            'challenge_timeout' => 1,
        ]));
        $this->assertCount(0, static::$container->get('validator')->validate($field));

        $field->setSettings(new FieldableFieldSettings([
            'secret_key' => 'foo',
            'expected_hostname' => 'https://baa.com',
            'score_threshold' => 0.0,
            'challenge_timeout' => 99999,
        ]));
        $this->assertCount(0, static::$container->get('validator')->validate($field));

        $field->setSettings(new FieldableFieldSettings([
            'secret_key' => 'foo',
            'expected_hostname' => 'https://baa.com/com',
        ]));
        $this->assertCount(0, static::$container->get('validator')->validate($field));

        $field->setSettings(new FieldableFieldSettings([
            'secret_key' => 'foo',
            'expected_hostname' => 'baa.com/com',
        ]));
        $this->assertCount(0, static::$container->get('validator')->validate($field));

        $field->setSettings(new FieldableFieldSettings([
            'secret_key' => 'foo',
            'expected_hostname' => 'baa.com/com.html',
        ]));
        $this->assertCount(0, static::$container->get('validator')->validate($field));
    }

    public function testValidateData() {

        $field = $this->createContentTypeField('recaptcha');
        $field->setSettings(new FieldableFieldSettings([
            'secret_key' => 'foo',
            'expected_hostname' => 'baa.com',
            'score_threshold' => 0.5,
            'challenge_timeout' => 100,
        ]));

        // If validation is not done for api, just skip recaptcha check.
        $this->assertCount(0, $this->assertRecaptchaResponse($field, null, '{"success": false}', false));
        $this->assertCount(0, $this->assertRecaptchaResponse($field, 'foo', '{"success": false}', false));

        // Assert missing captcha code.
        $errors = $this->assertRecaptchaResponse($field, '', '{"success": false, "hostname": "baa.com", "score": "0.9"}');
        $this->assertCount(1, $errors);
        $this->assertEquals('required', $errors->get(0)->getMessage());

        // Assert wrong captcha code.
        $errors = $this->assertRecaptchaResponse($field, 'foo', '{"success": false, "hostname": "baa.com", "score": "0.9"}');
        $this->assertCount(1, $errors);
        $this->assertEquals(ReCaptcha::E_UNKNOWN_ERROR, $errors->get(0)->getMessage());

        // Assert wrong hostname.
        $errors = $this->assertRecaptchaResponse($field, 'foo', '{"success": true, "hostname": "foo", "score": "0.9"}');
        $this->assertCount(1, $errors);
        $this->assertEquals(ReCaptcha::E_HOSTNAME_MISMATCH, $errors->get(0)->getMessage());

        // Assert score not met.
        $errors = $this->assertRecaptchaResponse($field, 'foo', '{"success": true, "hostname": "baa.com", "score": "0.4"}');
        $this->assertCount(1, $errors);
        $this->assertEquals(ReCaptcha::E_SCORE_THRESHOLD_NOT_MET, $errors->get(0)->getMessage());

        // Assert score not met.
        $challengeTs = date('Y-M-d\TH:i:s\Z', time() - 200);
        $errors = $this->assertRecaptchaResponse($field, 'foo', '{"success": true, "hostname": "baa.com", "score": "0.9", "challenge_ts": "'.$challengeTs.'"}');
        $this->assertCount(1, $errors);
        $this->assertEquals(ReCaptcha::E_CHALLENGE_TIMEOUT, $errors->get(0)->getMessage());

        // Assert correct code
        $this->assertCount(0, $this->assertRecaptchaResponse($field, 'foo', '{"success": true, "hostname": "baa.com", "score": "0.9"}'));
    }

    public function testValuesWillNotBeStoredToDatabase() {

        $ctField = $this->createContentTypeField('recaptcha');
        $otherField = new ContentTypeField();
        $otherField
            ->setType('text')
            ->setTitle($this->generateRandomMachineName(100))
            ->setIdentifier($this->generateRandomMachineName(100))
            ->setContentType($ctField->getContentType());

        $this->em->persist($ctField->getContentType()->getDomain()->getOrganization());
        $this->em->persist($ctField->getContentType()->getDomain());
        $this->em->persist($ctField->getContentType());
        $this->em->persist($otherField);
        $this->em->persist($ctField);
        $this->em->flush();

        $content = new Content();
        $content->setData(
            [
                $otherField->getIdentifier() => 'foo',
                $ctField->getIdentifier() => 'recaptvcha-code',
            ]
        )->setContentType($ctField->getContentType());

        $this->em->persist($content);
        $this->em->flush();

        $this->assertNotNull($content->getId());
        $refreshedContent = $this->em->getRepository(Content::class)->find($content->getId());

        // Make sure, that the recaptcha code was not saved to the database.
        $this->assertEquals([
            $otherField->getIdentifier() => 'foo',
        ], $refreshedContent->getData());

        // Now update the content and resave it.
        $refreshedContent->setData([
            $otherField->getIdentifier() => 'updated',
            $ctField->getIdentifier() => 'new recaptvcha-code',
        ]);
        $this->em->flush();

        $refreshedContent2 = $this->em->getRepository(Content::class)->find($content->getId());

        // Make sure, that the recaptcha code was not saved to the database.
        $this->assertEquals([
            $otherField->getIdentifier() => 'updated',
        ], $refreshedContent2->getData());
    }
}

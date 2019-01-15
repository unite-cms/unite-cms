<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 17.05.18
 * Time: 17:06
 */

namespace UniteCMS\CoreBundle\Tests\Expression;

use PHPUnit\Framework\TestCase;
use UniteCMS\CoreBundle\Entity\Content;
use UniteCMS\CoreBundle\Entity\ContentType;
use UniteCMS\CoreBundle\Entity\DomainMember;
use UniteCMS\CoreBundle\Entity\DomainMemberType;
use UniteCMS\CoreBundle\Entity\User;
use UniteCMS\CoreBundle\Expression\UniteExpressionChecker;

class UniteExpressionCheckerTest extends TestCase
{
    /**
     * @var UniteExpressionChecker $ACE
     */
    private $ACE;

    public function setUp()
    {
        $this->ACE = new UniteExpressionChecker();
        parent::setUp();
    }

    public function testValidatingExpression() {

        $this->ACE
            ->registerFieldableContent(null)
            ->registerDomainMember(null);

        $this->assertFalse($this->ACE->validate(''));
        $this->assertFalse($this->ACE->validate('('));
        $this->assertFalse($this->ACE->validate('foo'));

        $this->assertFalse($this->ACE->validate('data'));

        $this->assertTrue($this->ACE->validate('1'));
        $this->assertTrue($this->ACE->validate('true'));
        $this->assertTrue($this->ACE->validate('false'));
        $this->assertTrue($this->ACE->validate('member'));
        $this->assertTrue($this->ACE->validate('content'));

    }

    public function testEvaluationExpression() {

        $domainMember = new DomainMember();
        $domainMember
            ->setData([
                'f1' => [
                    'foo' => 23,
                    'baa' => 45,
                ],
                'f2' => true,
                'groups' => ['g1', 'g2']
            ]);
        $domainMember->setDomainMemberType(new DomainMemberType())->getDomainMemberType()->setIdentifier('tuser');
        $domainMember->setAccessor(new User())->getAccessor()->setName('Test User');

        $rct2_id = new \ReflectionProperty($domainMember->getAccessor(), 'id');
        $rct2_id->setAccessible(true);
        $rct2_id->setValue($domainMember->getAccessor(), 23);

        $content = new Content();
        $content
            ->setContentType(new ContentType())
            ->setLocale('en')
            ->setData([
                'f1' => true,
                'f2' => false,
                'groups' => ['g1', 'g3'],
            ]);

        $this->ACE
            ->registerFieldableContent($content)
            ->registerDomainMember($domainMember);

        $this->assertFalse($this->ACE->evaluateToBool('foo'));
        $this->assertFalse($this->ACE->evaluateToBool('('));
        $this->assertFalse($this->ACE->evaluateToBool('member.skdj'));

        $this->assertTrue($this->ACE->evaluateToBool('true'));
        $this->assertFalse($this->ACE->evaluateToBool('false'));

        $this->assertTrue($this->ACE->evaluateToBool('member'));
        $this->assertTrue($this->ACE->evaluateToBool('member.type in ["foo", "tuser"]'));
        $this->assertFalse($this->ACE->evaluateToBool('member.type == "foo"'));
        $this->assertFalse($this->ACE->evaluateToBool('member.accessor.id == 22'));
        $this->assertTrue($this->ACE->evaluateToBool('member.accessor.id == 23'));
        $this->assertFalse($this->ACE->evaluateToBool('member.accessor.name matches "/^[a-zA-Z]+$/"'));
        $this->assertTrue($this->ACE->evaluateToBool('member.accessor.name matches "/^[a-zA-Z ]+$/"'));
        $this->assertTrue($this->ACE->evaluateToBool('member.accessor.type  == "user"'));
        $this->assertFalse($this->ACE->evaluateToBool('member.accessor.type  == "api_key"'));

        $this->assertTrue($this->ACE->evaluateToBool('member.data.f1.foo > 20 && member.data.f1.baa > 40'));
        $this->assertTrue($this->ACE->evaluateToBool('member.data.f2'));

        $this->assertTrue($this->ACE->evaluateToBool('content.data.f1'));
        $this->assertFalse($this->ACE->evaluateToBool('content.data.f2'));
        $this->assertTrue($this->ACE->evaluateToBool('content.locale in ["de", "en"]'));

        $this->assertTrue($this->ACE->evaluateToBool('member.data.groups[0] in content.data.groups || member.data.groups[1] in content.data.groups || member.data.groups[2] in content.data.groups'));

        $this->ACE->clearVariables();
        $this->ACE->registerDomainMember($domainMember);
        $this->assertFalse($this->ACE->evaluateToBool('content.data.f1'));
    }
}
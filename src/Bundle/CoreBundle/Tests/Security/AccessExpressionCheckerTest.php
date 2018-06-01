<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 17.05.18
 * Time: 17:06
 */

namespace UniteCMS\CoreBundle\Tests\Security;


use UniteCMS\CoreBundle\Security\AccessExpressionChecker;
use PHPUnit\Framework\TestCase;
use UniteCMS\CoreBundle\Entity\Content;
use UniteCMS\CoreBundle\Entity\ContentType;
use UniteCMS\CoreBundle\Entity\DomainMember;
use UniteCMS\CoreBundle\Entity\DomainMemberType;
use UniteCMS\CoreBundle\Entity\User;

class AccessExpressionCheckerTest extends TestCase
{
    /**
     * @var AccessExpressionChecker $ACE
     */
    private $ACE;

    public function setUp()
    {
        $this->ACE = new AccessExpressionChecker();
        parent::setUp();
    }

    public function testValidatingExpression() {

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

        $this->assertFalse($this->ACE->evaluate('foo', $domainMember));
        $this->assertFalse($this->ACE->evaluate('(', $domainMember));
        $this->assertFalse($this->ACE->evaluate('member.skdj', $domainMember));

        $this->assertTrue($this->ACE->evaluate('true', $domainMember));
        $this->assertFalse($this->ACE->evaluate('false', $domainMember));

        $this->assertTrue($this->ACE->evaluate('member', $domainMember));
        $this->assertTrue($this->ACE->evaluate('member.type in ["foo", "tuser"]', $domainMember));
        $this->assertFalse($this->ACE->evaluate('member.type == "foo"', $domainMember));
        $this->assertFalse($this->ACE->evaluate('member.accessor.id == 22', $domainMember));
        $this->assertTrue($this->ACE->evaluate('member.accessor.id == 23', $domainMember));
        $this->assertFalse($this->ACE->evaluate('member.accessor.name matches "/^[a-zA-Z]+$/"', $domainMember));
        $this->assertTrue($this->ACE->evaluate('member.accessor.name matches "/^[a-zA-Z ]+$/"', $domainMember));
        $this->assertTrue($this->ACE->evaluate('member.accessor.type  == "user"', $domainMember));
        $this->assertFalse($this->ACE->evaluate('member.accessor.type  == "api_key"', $domainMember));

        $this->assertTrue($this->ACE->evaluate('member.data.f1.foo > 20 && member.data.f1.baa > 40', $domainMember));
        $this->assertTrue($this->ACE->evaluate('member.data.f2', $domainMember));

        $this->assertFalse($this->ACE->evaluate('content.data.f1', $domainMember));
        $this->assertTrue($this->ACE->evaluate('content.data.f1', $domainMember, $content));
        $this->assertFalse($this->ACE->evaluate('content.data.f2', $domainMember, $content));
        $this->assertTrue($this->ACE->evaluate('content.locale in ["de", "en"]', $domainMember, $content));

        $this->assertTrue($this->ACE->evaluate('member.data.groups[0] in content.data.groups || member.data.groups[1] in content.data.groups || member.data.groups[2] in content.data.groups', $domainMember, $content));
    }
}
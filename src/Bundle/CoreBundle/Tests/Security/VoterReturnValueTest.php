<?php

namespace src\UniteCMS\CoreBundle\Tests\Security;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use UniteCMS\CoreBundle\Entity\Content;
use UniteCMS\CoreBundle\Entity\ContentType;
use UniteCMS\CoreBundle\Entity\Domain;
use UniteCMS\CoreBundle\Entity\Organization;
use UniteCMS\CoreBundle\Entity\OrganizationMember;
use UniteCMS\CoreBundle\Entity\Setting;
use UniteCMS\CoreBundle\Entity\SettingType;
use UniteCMS\CoreBundle\Entity\User;
use UniteCMS\CoreBundle\Security\Voter\ContentVoter;
use UniteCMS\CoreBundle\Security\Voter\DeletedContentVoter;
use UniteCMS\CoreBundle\Security\Voter\DomainVoter;
use UniteCMS\CoreBundle\Security\Voter\OrganizationVoter;
use UniteCMS\CoreBundle\Security\Voter\SettingVoter;
use UniteCMS\CoreBundle\Tests\SecurityVoterTestCase;

class VoterReturnValueTest extends SecurityVoterTestCase
{
    /**
     * @var TokenInterface $token
     */
    private $token;

    public function setUp()
    {
        parent::setUp();
        $this->token = new UsernamePasswordToken(new User(), 'password', 'main', ['ROLE_USER']);
    }

    public function testVoteReturnsAlwaysValidValues() {
        $contentVoter = new class extends ContentVoter {
            public function voteWithoutCheck(TokenInterface $token, $subject, $attribute) {
                return $this->voteOnAttribute($attribute, $subject, $token);
            }
        };
        $this->assertEquals(VoterInterface::ACCESS_ABSTAIN, $contentVoter->voteWithoutCheck($this->token, (object)[], ''));

        $deleteContentVoter = new class extends DeletedContentVoter {
            public function voteWithoutCheck(TokenInterface $token, $subject, $attribute) {
                return $this->voteOnAttribute($attribute, $subject, $token);
            }
        };
        $this->assertEquals(VoterInterface::ACCESS_ABSTAIN, $deleteContentVoter->voteWithoutCheck($this->token, (object)[], ''));

        $domainVoter = new class extends DomainVoter {
            public function voteWithoutCheck(TokenInterface $token, $subject, $attribute) {
                return $this->voteOnAttribute($attribute, $subject, $token);
            }
        };
        $this->assertEquals(VoterInterface::ACCESS_ABSTAIN, $domainVoter->voteWithoutCheck($this->token, (object)[], ''));

        $organizationVoter = new class extends OrganizationVoter {
            public function voteWithoutCheck(TokenInterface $token, $subject, $attribute) {
                return $this->voteOnAttribute($attribute, $subject, $token);
            }
        };
        $this->assertEquals(VoterInterface::ACCESS_ABSTAIN, $organizationVoter->voteWithoutCheck($this->token, (object)[], ''));

        $settingVoter = new class extends SettingVoter {
            public function voteWithoutCheck(TokenInterface $token, $subject, $attribute) {
                return $this->voteOnAttribute($attribute, $subject, $token);
            }
        };
        $this->assertEquals(VoterInterface::ACCESS_ABSTAIN, $settingVoter->voteWithoutCheck($this->token, (object)[], ''));
    }

    public function testContentVoterReturnsAbstainForDeletedContent() {

        $contentVoter = new class extends ContentVoter {
            public function voteWithoutCheck(TokenInterface $token, $subject, $attribute) {
                return $this->voteOnAttribute($attribute, $subject, $token);
            }
        };
        $value = new Content();
        $reflector = new \ReflectionProperty(Content::class, 'deleted');
        $reflector->setAccessible(true);
        $reflector->setValue($value, new \DateTime());
        $this->assertEquals(VoterInterface::ACCESS_ABSTAIN, $contentVoter->voteWithoutCheck($this->token, $value, 'any'));
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Permission 'unsupported' was not found in ContentType 'any'
     */
    public function testContentVoterWrongAttributeException() {
        $contentVoter = new class extends ContentVoter {
            public function voteWithoutCheck(TokenInterface $token, $subject, $attribute) {
                return $this->voteOnAttribute($attribute, $subject, $token);
            }
        };
        $domain = new Domain();
        $ct = new ContentType();
        $ct->setTitle('any')->setDomain($domain);
        $value = new Content();
        $value->setContentType($ct);
        $contentVoter->voteWithoutCheck($this->token, $value, 'unsupported');
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Permission 'unsupported' was not found in SettingType 'any'
     */
    public function testSettingVoterWrongAttributeException() {
        $contentVoter = new class extends SettingVoter {
            public function voteWithoutCheck(TokenInterface $token, $subject, $attribute) {
                return $this->voteOnAttribute($attribute, $subject, $token);
            }
        };
        $domain = new Domain();
        $st = new SettingType();
        $st->setTitle('any')->setDomain($domain);
        $value = new Setting();
        $value->setSettingType($st);
        $contentVoter->voteWithoutCheck($this->token, $value, 'unsupported');
    }
}

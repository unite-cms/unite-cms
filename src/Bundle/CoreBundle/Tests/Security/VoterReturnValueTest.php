<?php

namespace src\UnitedCMS\CoreBundle\Tests\Security;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use UnitedCMS\CoreBundle\Entity\Content;
use UnitedCMS\CoreBundle\Entity\ContentType;
use UnitedCMS\CoreBundle\Entity\Domain;
use UnitedCMS\CoreBundle\Entity\DomainMember;
use UnitedCMS\CoreBundle\Entity\Organization;
use UnitedCMS\CoreBundle\Entity\OrganizationMember;
use UnitedCMS\CoreBundle\Entity\Setting;
use UnitedCMS\CoreBundle\Entity\SettingType;
use UnitedCMS\CoreBundle\Entity\User;
use UnitedCMS\CoreBundle\Security\ContentVoter;
use UnitedCMS\CoreBundle\Security\DeletedContentVoter;
use UnitedCMS\CoreBundle\Security\DomainVoter;
use UnitedCMS\CoreBundle\Security\OrganizationVoter;
use UnitedCMS\CoreBundle\Security\SettingVoter;
use UnitedCMS\CoreBundle\Tests\SecurityVoterTestCase;

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

    public function testOrganizationVoterUnknownOrganizationRole() {
        $organizationVoter = new class extends OrganizationVoter {
            public function voteWithoutCheck(TokenInterface $token, $subject, $attribute) {
                return $this->voteOnAttribute($attribute, $subject, $token);
            }
        };

        $value = new Organization();
        $value->setTitle('any');

        /** @var User $user */
        $user = $this->token->getUser();
        $member = new OrganizationMember();
        $member->setOrganization($value)->setRoles(['any_unknown_role']);
        $user->addOrganization($member);
        $this->assertEquals(VoterInterface::ACCESS_DENIED, $organizationVoter->voteWithoutCheck($this->token, $value, OrganizationVoter::VIEW));
    }

}
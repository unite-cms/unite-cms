<?php

namespace UniteCMS\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Invitation
 *
 * @ORM\Table(name="invitation")
 * @ORM\Entity()
 * @UniqueEntity(fields={"email", "organization"}, message="email_already_invited", ignoreNull=false, errorPath="email")
 * @UniqueEntity(fields={"token"}, message="token_already_present", errorPath="token")
 * @Assert\Callback(callback="emailNotAlreadyTaken")
 * @Assert\Callback(callback="domainInOrganization")
 */
class Invitation
{
    const INVITATION_RESET_TTL = 2592000; // Default to one month

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="bigint")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var Organization
     * @Assert\Valid()
     * @Assert\NotBlank(message="not_blank")
     * @ORM\ManyToOne(targetEntity="UniteCMS\CoreBundle\Entity\Organization", inversedBy="invites")
     * @ORM\JoinColumn(name="organization_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $organization;

    /**
     * @var DomainMemberType
     * @Assert\Valid()
     * @ORM\ManyToOne(targetEntity="UniteCMS\CoreBundle\Entity\DomainMemberType", inversedBy="invites")
     * @ORM\JoinColumn(name="domain_member_type_id", referencedColumnName="id", onDelete="CASCADE", nullable=true)
     */
    private $domainMemberType;

    /**
     * @var User
     * @Assert\NotBlank(message="not_blank")
     * @Assert\Email(message="invalid_email")
     * @ORM\Column(name="email", type="string")
     */
    private $email;

    /**
     * @var string
     * @Assert\Length(max="180", maxMessage="too_long")
     * @Assert\NotBlank(message="not_blank")
     * @Assert\Regex(pattern="/^[a-z0-9A-Z\-_]+$/i", message="invalid_characters")
     * @ORM\Column(name="token", type="string", length=180, unique=true, nullable=true)
     */
    protected $token;

    /**
     * @var \DateTime
     * @Assert\NotBlank(message="not_blank")
     * @ORM\Column(name="requested_at", type="datetime", nullable=true)
     */
    protected $requestedAt;

    public function __toString()
    {
        return $this->getEmail();
    }

    public function emailNotAlreadyTaken(ExecutionContextInterface $context)
    {
        if ($this->getOrganization()) {
            foreach ($this->getOrganization()->getMembers() as $organizationMember) {
                if ($organizationMember->getUser()->getEmail() === $this->getEmail()) {
                    $context->buildViolation('email_already_member')
                        ->atPath('email')
                        ->addViolation();
                }
            }
        }
    }

    public function domainInOrganization(ExecutionContextInterface $context)
    {
        if ($this->getOrganization() && $this->getDomainMemberType()) {
            if($this->getDomainMemberType()->getDomain()->getOrganization() !== $this->getOrganization()) {
                $context->buildViolation('invalid_domain_member_type')
                    ->atPath('domainMemberType')
                    ->addViolation();
            }
        }
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     *
     * @return Invitation
     */
    public function setId(int $id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return Organization
     */
    public function getOrganization()
    {
        return $this->organization;
    }

    /**
     * @param Organization $organization
     *
     * @return $this
     */
    public function setOrganization(Organization $organization)
    {
        $this->organization = $organization;

        return $this;
    }

    /**
     * @return DomainMemberType
     */
    public function getDomainMemberType()
    {
        return $this->domainMemberType;
    }

    /**
     * @param DomainMemberType $domainMemberType
     *
     * @return Invitation
     */
    public function setDomainMemberType($domainMemberType)
    {
        $this->domainMemberType = $domainMemberType;

        return $this;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param string $email
     *
     * @return Invitation
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * @param string $token
     *
     * @return  Invitation
     */
    public function setToken(string $token)
    {
        $this->token = $token;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getRequestedAt()
    {
        return $this->requestedAt;
    }

    /**
     * @param \DateTime $requestedAt
     *
     * @return Invitation
     */
    public function setRequestedAt(\DateTime $requestedAt)
    {
        $this->requestedAt = \DateTime::createFromFormat('d/m/Y H:i:s', $requestedAt->format('d/m/Y H:i:s'));

        return $this;
    }

    /**
     * Clears the current token and resets the requestedAt value.
     *
     * @return Invitation
     */
    public function clearToken()
    {
        $this->token = null;
        $this->requestedAt = null;

        return $this;
    }

    /**
     * Returns true, if the invite is expired.
     *
     * @param \DateTime $now , override the actual datetime
     * @param int $ttl , override the default ttl
     * @return bool
     */
    public function isExpired($now = null, $ttl = null)
    {

        if (!$this->getRequestedAt()) {
            return true;
        }

        $now = $now ?? new \DateTime();
        $ttl = $ttl ?? self::INVITATION_RESET_TTL;

        return ($this->getRequestedAt()->getTimestamp() + $ttl) <= $now->getTimestamp();
    }
}

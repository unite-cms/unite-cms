<?php

namespace UniteCMS\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;
use UniteCMS\CoreBundle\Validator\Constraints\ValidFieldableContentData;

/**
 * DomainMember
 *
 * @ORM\Table(name="domain_member")
 * @ORM\Entity
 * @Gedmo\Loggable
 * @UniqueEntity(fields={"domain", "accessor", "domainMemberType"}, message="user_already_member_of_domain_for_type")
 */
class DomainMember implements FieldableContent
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="bigint")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var Domain
     * @Assert\NotBlank(message="not_blank")
     * @Assert\Choice(callback="possibleDomains", strict=true, message="domain_organization")
     * @ORM\ManyToOne(targetEntity="UniteCMS\CoreBundle\Entity\Domain", inversedBy="members")
     * @ORM\JoinColumn(name="domain_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $domain;

    /**
     * @var DomainAccessor
     * @Assert\NotBlank(message="not_blank")
     * @Assert\Valid()
     * @ORM\ManyToOne(targetEntity="DomainAccessor", inversedBy="domains")
     */
    private $accessor;

    /**
     * @var DomainMemberType
     * @Assert\NotBlank(message="not_blank")
     * @ORM\ManyToOne(targetEntity="UniteCMS\CoreBundle\Entity\DomainMemberType", inversedBy="domainMembers", fetch="EXTRA_LAZY")
     * @ORM\JoinColumn(name="domain_member_type_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $domainMemberType;

    /**
     * @var array
     * @ValidFieldableContentData(additionalDataMessage="additional_data", groups={"Default", "DELETE"})
     * @Gedmo\Versioned
     * @ORM\Column(name="data", type="json", nullable=true)
     */
    protected $data = [];

    /**
     * @var \DateTime $created
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="datetime")
     */
    private $created;

    /**
     * @var \DateTime $updated
     *
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(type="datetime")
     */
    private $updated;

    public function __toString()
    {
        // If domain_member_label property is defined, use it.
        if (!empty($this->getDomainMemberType()) && !empty($this->getDomainMemberType()->getDomainMemberLabel())) {
            $string = $this->getDomainMemberType()->getDomainMemberLabel();

            // Find all variable placeholders in the domain member label.
            preg_match_all("/{([a-z0-9._]+)}/", $string, $output_array);
            if (count($output_array) == 2) {
                foreach ($output_array[1] as $value) {
                    if (($replacement = $this->findDataBySelector($value)) !== null) {
                        $string = str_replace('{'.$value.'}', $replacement, $string);
                    }
                }
            }

            return $string;
        }

        return ''. (string)$this->getAccessor();
    }

    /**
     * Returns possible nested data by a selector.
     * The following fields are defined per default: 'id', 'created', 'updated', 'type' and 'accessor'.
     * Additionally all data values can be selected. Nested values can be defined with a dot '.'.
     *
     * Examples:
     *   id
     *   created
     *   any_field
     *   any_field.any_sub_field
     *
     * @param string $selector
     * @param array $data , this param is used internal to recursively find nested values.
     *
     * @return string|null
     */
    public function findDataBySelector(string $selector, array $data = null)
    {

        // For the root call, $data is NULL. In this case we can select data root fields.
        if ($data === null) {
            $data = array_merge(
                $this->getData(),
                [
                    'id' => (string)$this->getId(),
                    'created' => ($this->getCreated() ? $this->getCreated()->format('Y-m-d H:i:s') : ''),
                    'updated' => ($this->getUpdated() ? $this->getUpdated()->format('Y-m-d H:i:s') : ''),
                    'type' => (string)$this->getDomainMemberType(),
                    'accessor' => (string)$this->getAccessor(),
                ]
            );
        }

        $selector_parts = explode('.', $selector);

        // If this is the deepest component of the selector, try to return from array.
        if (count($selector_parts) == 1) {
            return isset($data[$selector]) ? (string)$data[$selector] : null;
        }

        // if this is not the deepest component try to find this field in the data array.
        $top_selector = array_shift($selector_parts);

        return !empty($data[$top_selector]) ? $this->findDataBySelector(
            implode('.', $selector_parts),
            $data[$top_selector]
        ) : null;
    }

    /**
     * Return possible domains for this domain member. This are the domains from the accessors organizations.
     * @return array
     */
    public function possibleDomains(): array
    {
        $domains = [];

        if(!$this->getAccessor()) {
            return $domains;
        }

        foreach($this->getAccessor()->getAccessibleOrganizations() as $organization) {
            foreach($organization->getDomains() as $domain) {
                $domains[] = $domain;
            }
        }

        return $domains;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     *
     * @return DomainMember
     */
    public function setId(int $id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return Domain
     */
    public function getDomain()
    {
        return $this->domain;
    }

    /**
     * @param Domain $domain
     *
     * @return DomainMember
     */
    public function setDomain(Domain $domain)
    {
        $this->domain = $domain;

        return $this;
    }

    /**
     * @return DomainAccessor
     */
    public function getAccessor()
    {
        return $this->accessor;
    }

    /**
     * @param DomainAccessor $accessor
     *
     * @return DomainMember
     */
    public function setAccessor(DomainAccessor $accessor)
    {
        $this->accessor = $accessor;

        return $this;
    }

    /**
     * @param Fieldable $entity
     *
     * @return DomainMember
     */
    public function setEntity(Fieldable $entity)
    {
        if ($entity instanceof DomainMemberType) {
            $this->setDomainMemberType($entity);
        }

        return $this;
    }

    /**
     * @return Fieldable
     */
    public function getEntity()
    {
        return $this->getDomainMemberType();
    }

    /**
     * @param DomainMemberType $domainMemberType
     *
     * @return DomainMember
     */
    public function setDomainMemberType(DomainMemberType $domainMemberType)
    {
        $this->domainMemberType = $domainMemberType;

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
     * DomainMembers do not support setting locales.
     * @return null|string
     */
    public function getLocale()
    {
        return null;
    }

    /**
     * DomainMembers do not support setting locales.
     * @param null|string $locale
     * @return DomainMember
     */
    public function setLocale($locale)
    {
        return $this;
    }

    /**
     * Set data
     *
     * @param array $data
     *
     * @return DomainMember
     */
    public function setData(array $data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Get data
     *
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * @return \DateTime
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * @return \DateTime
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * {@inheritdoc}
     */
    public function isNew(): bool {
        return empty($this->getId());
    }
}

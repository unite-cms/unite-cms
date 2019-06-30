<?php

namespace UniteCMS\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;
use UniteCMS\CoreBundle\Validator\Constraints\ValidFieldableContentData;
use UniteCMS\CoreBundle\Validator\Constraints\ValidFieldableContentLocale;

/**
 * Setting
 *
 * @ORM\Table(name="setting")
 * @Gedmo\Loggable(logEntryClass="UniteCMS\CoreBundle\Entity\ContentLogEntry")
 * @ORM\Entity
 */
class Setting implements FieldableContent
{
    /**
     * @var int
     *
     * @ORM\Column(type="guid")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="UUID")
     */
    protected $id;

    /**
     * @var SettingType
     * @Assert\NotBlank(message="not_blank")
     * @ORM\ManyToOne(targetEntity="UniteCMS\CoreBundle\Entity\SettingType", inversedBy="settings", fetch="EXTRA_LAZY")
     * @ORM\JoinColumn(name="setting_type_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $settingType;

    /**
     * @var string
     * @Assert\Locale(canonicalize=true)
     * @ValidFieldableContentLocale(message="invalid_locale")
     * @ORM\Column(type="string", nullable=true)
     */
    protected $locale;

    /**
     * @var array
     * @ValidFieldableContentData(additionalDataMessage="additional_data")
     * @Gedmo\Versioned
     * @ORM\Column(name="data", type="json", nullable=true)
     */
    protected $data = [];

    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param Fieldable $entity
     *
     * @return Setting
     */
    public function setEntity(Fieldable $entity)
    {
        if ($entity instanceof SettingType) {
            $this->setSettingType($entity);
        }

        return $this;
    }

    /**
     * @return Fieldable
     */
    public function getEntity()
    {
        return $this->getSettingType();
    }

    /**
     * @param SettingType $settingType
     *
     * @return Setting
     */
    public function setSettingType(SettingType $settingType)
    {
        $this->settingType = $settingType;
        $settingType->addSetting($this);

        return $this;
    }

    /**
     * @return SettingType
     */
    public function getSettingType()
    {
        return $this->settingType;
    }

    /**
     * @return string
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * @param string|null $locale
     *
     * @return Setting
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;

        return $this;
    }

    /**
     * Set data
     *
     * @param array $data
     *
     * @return Setting
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
     * {@inheritdoc}
     */
    public function isNew(): bool {
        return empty($this->getId());
    }

    /**
     * @return FieldableContent
     */
    public function getRootFieldableContent(): FieldableContent
    {
        return $this;
    }
}

<?php


namespace App\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use UniteCMS\CoreBundle\Content\ContentInterface;
use UniteCMS\CoreBundle\Content\FieldData;
use UniteCMS\CoreBundle\Content\BaseContent;
use UniteCMS\CoreBundle\Content\ReferenceFieldData;

/**
 * @ORM\Table(name="article")
 * @ORM\Entity(repositoryClass="UniteCMS\DoctrineORMBundle\Repository\ContentRepository")
 * @UniqueEntity({"locale", "translate"}, errorPath="locale", message="You cannot add two translations with the same locale.")
 */
class Article extends BaseContent
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue("UUID")
     * @ORM\Column(type="guid")
     */
    protected $id;

    /**
     * @ORM\Column(type="string")
     */
    protected $name;

    /**
     * @var Article
     *
     * @ORM\ManyToOne(targetEntity="UniteCMS\DoctrineORMBundle\Entity\Content")
     */
    protected $main_category;

    /**
     * @ORM\Column(type="string")
     */
    protected $type;

    /**
     * @var FieldData[]
     *
     * @ORM\Column(type="json_document", options={"jsonb": true})
     */
    protected $data = [];

    /**
     * @var DateTime
     *
     * @ORM\Column(type="datetime")
     */
    protected $created;

    /**
     * @var DateTime
     *
     * @ORM\Column(type="datetime")
     */
    protected $updated;

    /**
     * @var DateTime
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $deleted = null;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", nullable=true)
     */
    protected $locale = null;

    /**
     * @var Article
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Article", inversedBy="translations")
     * @ORM\JoinColumn(name="translate_id", referencedColumnName="id", onDelete="SET NULL")
     */
    protected $translate;

    /**
     * @var Article[]
     *
     * @ORM\OneToMany(targetEntity="App\Entity\Article", mappedBy="translate")
     */
    protected $translations;

    /**
     * {@inheritDoc}
     */
    public function getData(): array
    {
        if(!is_array($this->data)) {
            $this->data = [];
        }

        $this->data['locale'] = new FieldData($this->getLocale());
        $this->data['name'] = new FieldData($this->name);
        $this->data['main_category'] = $this->main_category ? new ReferenceFieldData($this->main_category->getId()) : null;

        return $this->data;
    }

    /**
     * @param FieldData[] $data
     * @return self
     */
    public function setData(array $data) : ContentInterface
    {
        foreach($data as $key => $value) {
            switch ($key) {
                case 'name':
                    $this->name = $value->resolveData();
                    break;
                case 'main_category':
                    $this->main_category = $value->resolveData();
                    break;
            }
        }

        $this->data = $data;
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getFieldData(string $fieldName): ?FieldData
    {
        return isset($this->getData()[$fieldName]) ? $this->getData()[$fieldName] : null;
    }
}

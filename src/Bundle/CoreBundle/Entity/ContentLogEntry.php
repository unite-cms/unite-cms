<?php


namespace UniteCMS\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Loggable\Entity\LogEntry;

/**
 * @ORM\Table(
 *     name="ext_log_entries",
 *     options={"row_format":"DYNAMIC"},
 *  indexes={
 *      @ORM\Index(name="log_class_lookup_idx", columns={"object_class"}),
 *      @ORM\Index(name="log_date_lookup_idx", columns={"logged_at"}),
 *      @ORM\Index(name="log_user_lookup_idx", columns={"username"}),
 *      @ORM\Index(name="log_version_lookup_idx", columns={"object_id", "object_class", "version"})
 *  }
 * )
 * @ORM\Entity(repositoryClass="Gedmo\Loggable\Entity\Repository\LogEntryRepository")
 */
class ContentLogEntry extends LogEntry
{
    /**
     * @var DomainAccessor
     * @ORM\ManyToOne(targetEntity="DomainAccessor", fetch="EXTRA_LAZY")
     * @ORM\JoinColumn(name="accessor_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    private $accessor;

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
     * @return ContentLogEntry
     */
    public function setAccessor(DomainAccessor $accessor)
    {
        $this->accessor = $accessor;

        return $this;
    }
}

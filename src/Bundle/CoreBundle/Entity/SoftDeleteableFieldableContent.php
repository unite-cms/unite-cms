<?php


namespace UniteCMS\CoreBundle\Entity;

use DateTime;

interface SoftDeleteableFieldableContent extends FieldableContent
{
    /**
     * @return \DateTime|null
     */
    public function getDeleted() : ?DateTime;

    /**
     * @return SoftDeleteableFieldableContent
     */
    public function recoverDeleted() : SoftDeleteableFieldableContent;
}

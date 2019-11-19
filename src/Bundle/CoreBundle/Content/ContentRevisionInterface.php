<?php


namespace UniteCMS\CoreBundle\Content;

use DateTime;

interface ContentRevisionInterface
{
    /**
     * @return string
     */
    public function getEntityId(): string;

    /**
     * @return string
     */
    public function getEntityType() : string;

    /**
     * @return string
     */
    public function getOperation() : string;

    /**
     * @return int
     */
    public function getVersion(): int;

    /**
     * @return DateTime
     */
    public function getOperationTime() : DateTime;

    /**
     * @return string
     */
    public function getOperatorName() : string;

    /**
     * @return null|string
     */
    public function getOperatorType() : ?string;

    /**
     * @return null|string
     */
    public function getOperatorId() : ?string;

    /**
     * @return FieldData[]
     */
    public function getData(): array;
}

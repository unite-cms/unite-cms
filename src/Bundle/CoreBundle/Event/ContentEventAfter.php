<?php


namespace UniteCMS\CoreBundle\Event;

class ContentEventAfter extends ContentEvent {
    const CREATE = 'AFTER CREATE';
    const UPDATE = 'AFTER UPDATE';
    const REVERT = 'AFTER REVERT';
    const DELETE = 'AFTER DELETE';
    const RECOVER = 'AFTER RECOVER';
    const PERMANENT_DELETE = 'AFTER PERMANENT_DELETE';
}

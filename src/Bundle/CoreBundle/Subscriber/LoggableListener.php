<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 04.06.18
 * Time: 09:01
 */

namespace UniteCMS\CoreBundle\Subscriber;

use Doctrine\Common\EventArgs;
use Gedmo\Loggable\Entity\LogEntry;
use Gedmo\Loggable\LoggableListener as BaseLoggableListener;
use UniteCMS\CoreBundle\Entity\Content;

class LoggableListener extends BaseLoggableListener
{

    const ACTION_RECOVER = 'recover';

    /**
     * {@inheritdoc}
     */
    public function onFlush(EventArgs $eventArgs)
    {
        $ea = $this->getEventAdapter($eventArgs);
        $om = $ea->getObjectManager();
        $uow = $om->getUnitOfWork();

        foreach ($ea->getScheduledObjectInsertions($uow) as $object) {
            $this->createLogEntry(self::ACTION_CREATE, $object, $ea);
        }
        foreach ($ea->getScheduledObjectUpdates($uow) as $object) {

            $updateAction = self::ACTION_UPDATE;

            // If this content update changes the delete property from not null to null, this is a recover action.
            if($object instanceof Content) {
                $changeSet = $uow->getEntityChangeSet($object);
                if (array_key_exists('deleted', $changeSet) && $changeSet['deleted'][0] !== null && $changeSet['deleted'][1] === null) {
                    $updateAction = self::ACTION_RECOVER;
                }
            }

            $this->createLogEntry($updateAction, $object, $ea);
        }

        foreach ($ea->getScheduledObjectDeletions($uow) as $object) {

            // We only want to create an delete log entry for content objects.
            if($object instanceof Content && $object->getDeleted() === null) {
                $this->createLogEntry(self::ACTION_REMOVE, $object, $ea);
            }
        }
    }

    /**
     * Handle any custom LogEntry functionality that needs to be performed
     * before persisting it
     *
     * @param object $logEntry The LogEntry being persisted
     * @param object $object   The object being Logged
     */
    protected function prePersistLogEntry($logEntry, $object)
    {
        // Set original data from before delete an recover action.
        if ($logEntry->getAction() === self::ACTION_RECOVER && $object instanceof Content) {
           $logEntry->setData(['data' => $object->getData()]);
        }
    }

}
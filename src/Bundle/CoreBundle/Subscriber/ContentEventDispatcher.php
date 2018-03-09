<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 02.02.18
 * Time: 13:12
 */

namespace UnitedCMS\CoreBundle\Subscriber;

use Doctrine\Common\Persistence\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use UnitedCMS\CoreBundle\Entity\Content;
use UnitedCMS\CoreBundle\Entity\Setting;
use UnitedCMS\CoreBundle\Field\FieldTypeManager;

class ContentEventDispatcher
{
    private $fieldTypeManager;

    public function __construct(FieldTypeManager $fieldTypeManager)
    {
        $this->fieldTypeManager = $fieldTypeManager;
    }

    public function prePersist(LifecycleEventArgs $args) {

        $entity = $args->getObject();

        // Notify all field types about an insert event.
        if ($entity instanceof Content) {
            foreach($entity->getContentType()->getFields() as $field) {
                $this->fieldTypeManager->onContentInsert($field, $entity, $args);
            }
        }

    }

    public function preUpdate(PreUpdateEventArgs $args) {

        $entity = $args->getObject();

        // Notify all field types about an update event.
        if ($entity instanceof Content) {
            foreach($entity->getContentType()->getFields() as $field) {
                $this->fieldTypeManager->onContentUpdate($field, $entity, $args);
            }
        }

        // Notify all field types about an update event.
        if ($entity instanceof Setting) {
            foreach($entity->getSettingType()->getFields() as $field) {
                $this->fieldTypeManager->onSettingUpdate($field, $entity, $args);
            }
        }

    }

    public function preRemove(LifecycleEventArgs $args) {

        $entity = $args->getObject();

        // Notify all field types about a remove event.
        if ($entity instanceof Content) {
            foreach($entity->getContentType()->getFields() as $field) {
                $this->fieldTypeManager->onContentRemove($field, $entity, $args);
            }
        }

    }

}
<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 01.06.18
 * Time: 16:08
 */

namespace UniteCMS\CoreBundle\Subscriber;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\LifecycleEventArgs;
use UniteCMS\CoreBundle\Entity\ContentType;
use UniteCMS\CoreBundle\Entity\DomainMemberType;
use UniteCMS\CoreBundle\Entity\FieldableContent;
use UniteCMS\CoreBundle\Entity\SettingType;

class DeleteFieldableContentSubscriber
{
    public function preRemove(LifecycleEventArgs $args)
    {
        $object = $args->getObject();

        if($object instanceof ContentType) {
            $args->getEntityManager()->getFilters()->disable('gedmo_softdeleteable');
            foreach($args->getEntityManager()->getRepository('UniteCMSCoreBundle:Content')->findBy(['contentType' => $object]) as $content) {
                $this->deleteLogForFieldableContent($content, $args->getEntityManager());
            }
            $args->getEntityManager()->getFilters()->enable('gedmo_softdeleteable');
        }

        if($object instanceof SettingType) {
            foreach($object->getSettings() as $setting) {
                $this->deleteLogForFieldableContent($setting, $args->getEntityManager());
            }
        }

        if($object instanceof DomainMemberType) {
            foreach($object->getDomainMembers() as $member) {
                $this->deleteLogForFieldableContent($member, $args->getEntityManager());
            }
        }

        if ($object instanceof FieldableContent) {
            $this->deleteLogForFieldableContent($object, $args->getEntityManager());
        }
    }

    private function deleteLogForFieldableContent(FieldableContent $content, EntityManager $em) {
        foreach ($em->getRepository('GedmoLoggable:LogEntry')->getLogEntries($content) as $logEntry) {
            $em->remove($logEntry);
        }
    }
}
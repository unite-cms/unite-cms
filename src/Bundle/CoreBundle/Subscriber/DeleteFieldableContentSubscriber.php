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
use UniteCMS\CoreBundle\Entity\Content;
use UniteCMS\CoreBundle\Entity\ContentLogEntry;
use UniteCMS\CoreBundle\Entity\ContentType;
use UniteCMS\CoreBundle\Entity\DomainMember;
use UniteCMS\CoreBundle\Entity\FieldableContent;
use UniteCMS\CoreBundle\Entity\Setting;

class DeleteFieldableContentSubscriber
{
    public function preRemove(LifecycleEventArgs $args)
    {
        $object = $args->getObject();

        if ($object instanceof Setting || $object instanceof DomainMember) {
            $this->deleteLogForFieldableContent($object, $args->getEntityManager());
        }

        if($object instanceof Content && $object->getDeleted() !== null) {
            $this->deleteLogForFieldableContent($object, $args->getEntityManager());
        }

        if($object instanceof ContentType) {
            $args->getEntityManager()->getFilters()->disable('gedmo_softdeleteable');
            foreach($args->getEntityManager()->getRepository('UniteCMSCoreBundle:Content')->findBy(['contentType' => $object]) as $content) {
                $this->deleteLogForFieldableContent($content, $args->getEntityManager());
            }
            $args->getEntityManager()->getFilters()->enable('gedmo_softdeleteable');
        }
    }

    private function deleteLogForFieldableContent(FieldableContent $content, EntityManager $em) {
        foreach ($em->getRepository(ContentLogEntry::class)->getLogEntries($content) as $logEntry) {
            $em->remove($logEntry);
        }
    }
}
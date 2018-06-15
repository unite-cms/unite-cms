<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 01.06.18
 * Time: 16:08
 */

namespace UniteCMS\CoreBundle\Subscriber;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Event\LifecycleEventArgs;
use UniteCMS\CoreBundle\Entity\ContentType;
use UniteCMS\CoreBundle\Entity\ContentTypeField;
use UniteCMS\CoreBundle\Entity\DomainMemberType;
use UniteCMS\CoreBundle\Entity\DomainMemberTypeField;
use UniteCMS\CoreBundle\Entity\FieldableField;
use UniteCMS\CoreBundle\Entity\SettingType;
use UniteCMS\CoreBundle\Entity\SettingTypeField;

class DeleteFieldableFieldSubscriber
{
    public function preRemove(LifecycleEventArgs $args)
    {
        $object = $args->getObject();

        // Delete field values for all fieldable field instances.
        if ($object instanceof ContentTypeField) {

            $softDeleteFilterEnabled = $args->getEntityManager()->getFilters()->isEnabled('gedmo_softdeleteable');

            // If filter was enabled before, disable it for the following operation.
            if($softDeleteFilterEnabled) {
                $args->getEntityManager()->getFilters()->disable('gedmo_softdeleteable');
            }

            $this->deleteFieldContent($args->getEntityManager()->getRepository('UniteCMSCoreBundle:Content'), $object);

            // If filter was enabled before, enable it again.
            if($softDeleteFilterEnabled) {
                $args->getEntityManager()->getFilters()->enable('gedmo_softdeleteable');
            }
        }

        if ($object instanceof SettingTypeField) {
            $this->deleteFieldContent($args->getEntityManager()->getRepository('UniteCMSCoreBundle:Setting'), $object);
        }

        if ($object instanceof DomainMemberTypeField) {
            $this->deleteFieldContent($args->getEntityManager()->getRepository('UniteCMSCoreBundle:DomainMember'), $object);
        }
    }

    private function deleteFieldContent(EntityRepository $repository, FieldableField $field) {

        $fieldName = null;

        if($field->getEntity() instanceof ContentType) {
            $fieldName = 'contentType';
        }

        else if($field->getEntity() instanceof SettingType) {
            $fieldName = 'settingType';
        }

        else if($field->getEntity() instanceof DomainMemberType) {
            $fieldName = 'domainMemberType';
        }

        if(!$fieldName) {
            return;
        }

        $query = $repository->createQueryBuilder('c')
            ->update()
            ->set('c.data', "JSON_REMOVE(c.data, :identifier)")
            ->where('c.'.$fieldName.' = :type')
            ->setParameters(
                [
                    'identifier' => $field->getJsonExtractIdentifier(),
                    ':type' => $field->getEntity(),
                ]
            )
            ->getQuery();
        $query->execute();
    }
}
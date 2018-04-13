<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 11.11.17
 * Time: 13:33
 */

namespace UniteCMS\CoreBundle\View;

use JMS\Serializer\Context;
use JMS\Serializer\GraphNavigator;
use JMS\Serializer\Metadata\StaticPropertyMetadata;
use JMS\Serializer\VisitorInterface;

class ViewSettingsSerializationHandler
{
    public function handle(VisitorInterface $visitor, $data, $type, Context $context)
    {
        /**
         * @see \JMS\Serializer\Handler\StdClassHandler
         */
        if($context->getDirection() == GraphNavigator::DIRECTION_SERIALIZATION) {

            $classMetadata = $context->getMetadataFactory()->getMetadataForClass(ViewSettings::class);
            $visitor->startVisitingObject($classMetadata, $data, array('name' => ViewSettings::class), $context);

            foreach ((array)$data as $name => $value) {
                $metadata = new StaticPropertyMetadata(ViewSettings::class, $name, $value);
                $visitor->visitProperty($metadata, $value, $context);
            }

            return $visitor->endVisitingObject($classMetadata, $data, array('name' => ViewSettings::class), $context);
        }

        else if($context->getDirection() == GraphNavigator::DIRECTION_DESERIALIZATION) {
            return new ViewSettings((array)$data);
        }
    }
}

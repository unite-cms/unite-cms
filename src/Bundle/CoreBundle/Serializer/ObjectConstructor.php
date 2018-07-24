<?php

namespace UniteCMS\CoreBundle\Serializer;

use JMS\Serializer\Construction\ObjectConstructorInterface;
use JMS\Serializer\DeserializationContext;
use JMS\Serializer\Metadata\ClassMetadata;
use JMS\Serializer\VisitorInterface;

class ObjectConstructor implements ObjectConstructorInterface
{

    /**
     * Constructs a new object.
     *
     * Implementations could for example create a new object calling "new", use
     * "unserialize" techniques, reflection, or other means.
     *
     * @param VisitorInterface $visitor
     * @param ClassMetadata $metadata
     * @param mixed $data
     * @param array $type ["name" => string, "params" => array]
     * @param DeserializationContext $context
     *
     * @return object
     */
    public function construct(
        VisitorInterface $visitor,
        ClassMetadata $metadata,
        $data,
        array $type,
        DeserializationContext $context
    ) {
        $instance = null;

        // If this object has an constructor, try to fill in all required parameters.
        if($metadata->reflection->hasMethod('__construct')) {
            $parameters = [];
            foreach($metadata->reflection->getMethod('__construct')->getParameters() as $parameter) {
                if(array_key_exists($parameter->getName(), $data) && !$parameter->isOptional()) {
                    $value = $data[$parameter->getName()];

                    if(is_array($value) && $parameter->getType()->getName() === 'array') {
                        $parameters[$parameter->getName()] = $value;
                    }

                    if(is_string($value) && $parameter->getType()->getName() === 'string') {
                        $parameters[$parameter->getName()] = $value;
                    }
                }
            }

            $instance = $metadata->reflection->newInstanceArgs($parameters);
        } else {
            $instance = $metadata->reflection->newInstance();
        }

        return $instance;
    }
}

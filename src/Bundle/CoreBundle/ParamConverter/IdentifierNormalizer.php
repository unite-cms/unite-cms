<?php

namespace UniteCMS\CoreBundle\ParamConverter;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use Symfony\Component\HttpFoundation\Request;
use UniteCMS\CoreBundle\Entity\ContentType;
use UniteCMS\CoreBundle\Entity\Domain;
use UniteCMS\CoreBundle\Entity\DomainMemberType;
use UniteCMS\CoreBundle\Entity\Organization;
use UniteCMS\CoreBundle\Entity\SettingType;
use UniteCMS\CoreBundle\Entity\View;

/**
 * Converts identifiers from URL (including "-") to internal identifier (including "_") and vis-vi.
 */
class IdentifierNormalizer implements ParamConverterInterface
{
    const SUPPORTED_CLASSES = [
        Organization::class,
        Domain::class,
        ContentType::class,
        SettingType::class,
        DomainMemberType::class,
        View::class,
    ];

    static function normalize($identifier) {
        return str_replace('-', '_', $identifier);
    }

    static function denormalize($identifier) {
        return str_replace('_', '-', $identifier);
    }

    /**
     * {@inheritdoc}
     */
    public function apply(Request $request, ParamConverter $configuration)
    {
        $identifier = $request->attributes->get($configuration->getName());
        if(is_string($identifier)) {
            $request->attributes->set($configuration->getName(), static::normalize($identifier));
        }

        # Other converters should continue after this one. We only normalize the identifier but don't look for the object here.
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(ParamConverter $configuration)
    {
        return in_array($configuration->getClass(), self::SUPPORTED_CLASSES);
    }
}

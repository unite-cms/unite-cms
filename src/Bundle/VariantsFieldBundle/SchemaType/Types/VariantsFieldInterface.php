<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 06.08.18
 * Time: 09:26
 */

namespace UniteCMS\VariantsFieldBundle\SchemaType\Types;

use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\Type;
use UniteCMS\CoreBundle\SchemaType\SchemaTypeManager;
use UniteCMS\VariantsFieldBundle\Model\Variant;
use UniteCMS\VariantsFieldBundle\SchemaType\Factories\VariantFactory;

class VariantsFieldInterface extends InterfaceType
{
    /**
     * @var SchemaTypeManager $schemaTypeManager
     */
    private $schemaTypeManager;

    public function __construct(SchemaTypeManager $schemaTypeManager)
    {
        $this->schemaTypeManager = $schemaTypeManager;

        parent::__construct(
            [
                'fields' => function () {
                    return [
                        'type' => Type::string(),
                    ];
                },
                'resolveType' => function ($value) use ($schemaTypeManager) {

                    if(!$value instanceof Variant) {
                        throw new \InvalidArgumentException(
                            'Value must be instance of '.Variant::class.'.'
                        );
                    }

                    // For empty data we can resolve to a generic fallback type.
                    if(!$value->getIdentifier()) {
                        return $schemaTypeManager->getSchemaType('VariantsFieldBaseVariant');
                    }

                    // For real types, we can return the schema object that was generated before by VariantsFieldType.
                    return $schemaTypeManager->getSchemaType(VariantFactory::schemaTypeNameForVariant($value), $value->getRootEntity()->getDomain());
                },
            ]
        );
    }
}
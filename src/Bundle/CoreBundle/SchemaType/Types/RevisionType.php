<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 2018-12-17
 * Time: 09:24
 */

namespace UniteCMS\CoreBundle\SchemaType\Types;

use GraphQL\Type\Definition\ResolveInfo;
use InvalidArgumentException;
use UniteCMS\CoreBundle\Entity\ContentLogEntry;
use UniteCMS\CoreBundle\Entity\FieldableContent;
use UniteCMS\CoreBundle\SchemaType\SchemaTypeManager;

class RevisionType extends AbstractType
{
    protected $schemaTypeManager;
    protected $fieldableContentType;

    public function __construct(SchemaTypeManager $schemaTypeManager, string $fieldableContentType, string $name = null)
    {
        $this->schemaTypeManager = $schemaTypeManager;
        $this->fieldableContentType = $fieldableContentType;
        parent::__construct();
        $this->name = $name;
    }

    /**
     * {@inheritdoc}
     */
    protected function fields()
    {
        return [
            'version' => self::nonNull(self::int()),
            'author' => self::string(),
            'date' => self::nonNull(self::int()),
            'action' => self::nonNull(self::string()),
            'content' => $this->schemaTypeManager->getSchemaType($this->fieldableContentType),
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function resolveField($value, array $args, $context, ResolveInfo $info)
    {
        if (!is_array($value) || count($value) !== 2 || !$value[0] instanceof ContentLogEntry || !$value[1] instanceof FieldableContent) {
            throw new InvalidArgumentException('Value must be an array of a '.ContentLogEntry::class.' and a '.FieldableContent::class.'.');
        }

        switch ($info->fieldName) {
            case 'version' : return $value[0]->getVersion();
            case 'author' : return $value[0]->getUsername();
            case 'date' : return $value[0]->getLoggedAt()->getTimestamp();
            case 'action' : return $value[0]->getAction();
            case 'content' : return $value[1];
            default: return null;
        }
    }
}

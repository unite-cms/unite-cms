<?php


namespace UniteCMS\CoreBundle\Field\Types;

use UniteCMS\CoreBundle\Content\ContentInterface;
use UniteCMS\CoreBundle\ContentType\ContentTypeField;
use UniteCMS\CoreBundle\Domain\DomainManager;
use UniteCMS\CoreBundle\Field\FieldTypeInterface;

class ReferenceType implements FieldTypeInterface
{
    const TYPE = 'reference';

    /**
     * @var \UniteCMS\CoreBundle\Domain\DomainManager $domainManager
     */
    protected $domainManager;

    public function __construct(DomainManager $domainManager)
    {
        $this->domainManager = $domainManager;
    }

    /**
     * {@inheritDoc}
     */
    static function getType(): string {
        return self::TYPE;
    }

    /**
     * {@inheritDoc}
     */
    public function GraphQLInputType(ContentTypeField $field) : string {
        return 'UniteReferenceInput';
    }

    /**
     * {@inheritDoc}
     */
    public function resolveField(string $fieldName, ContentInterface $content, ContentTypeField $field) {
        // TODO: Implement
        //return $content->getFieldData($fieldName);

        $domain = $this->domainManager->current();
        $contentManager = $domain->getContentManager();

        return $field->isListOf() ?
            $contentManager->find($domain, $field->getReturnType())->getResult() :
            $contentManager->get($domain, $field->getReturnType(), 1);
    }
}

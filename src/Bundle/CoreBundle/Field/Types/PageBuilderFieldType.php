<?php

namespace UniteCMS\CoreBundle\Field\Types;

use Doctrine\Common\Collections\ArrayCollection;
use UniteCMS\AdminBundle\AdminView\AdminViewTypeManager;
use UniteCMS\CoreBundle\Content\ContentInterface;
use UniteCMS\CoreBundle\Content\FieldData;
use UniteCMS\CoreBundle\ContentType\ContentTypeField;
use UniteCMS\CoreBundle\Domain\DomainManager;
use UniteCMS\CoreBundle\Expression\SaveExpressionLanguage;

class PageBuilderFieldType extends AbstractFieldType
{
    const TYPE = 'pageBuilder';
    const GRAPHQL_INPUT_TYPE = 'UnitePageBuilderInput';

    /**
     * @var DomainManager $domainManager
     */
    protected $domainManager;

    /**
     * @var AdminViewTypeManager $adminViewTypeManager
     */
    protected $adminViewTypeManager;

    public function __construct(SaveExpressionLanguage $expressionLanguage, DomainManager $domainManager, AdminViewTypeManager $adminViewTypeManager)
    {
        parent::__construct($expressionLanguage);
        $this->domainManager = $domainManager;
        $this->adminViewTypeManager = $adminViewTypeManager;
    }

    /**
     * {@inheritDoc}
     */
    protected function allowedReturnTypes(ContentTypeField $field) {
        return ['UnitePageBuilderResult'];
    }

    /**
     * {@inheritDoc}
     */
    protected function resolveRowData(ContentInterface $content, ContentTypeField $field, FieldData $fieldData, array $args = []) {
        return [
            'rendered' => $fieldData->resolveData('content', ''),
            'nodes' => $fieldData->resolveData('nodes', []),
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getPublicSettings(ContentTypeField $field) : ?ArrayCollection {
        return new ArrayCollection([
            'customBlocks' => $field->getSettings()->get('customBlocks', []),
            'mediaType' => null,
            'templateType' => null,
        ]);
    }
}

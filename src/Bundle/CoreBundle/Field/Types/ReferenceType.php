<?php


namespace UniteCMS\CoreBundle\Field\Types;

use GraphQL\Type\Definition\Type;
use UniteCMS\CoreBundle\Content\ContentFilterInput;
use UniteCMS\CoreBundle\Content\ContentInterface;
use UniteCMS\CoreBundle\Content\FieldData;
use UniteCMS\CoreBundle\Content\FieldDataList;
use UniteCMS\CoreBundle\ContentType\ContentTypeField;
use UniteCMS\CoreBundle\Domain\DomainManager;

class ReferenceType extends AbstractFieldType
{
    const TYPE = 'reference';
    const GRAPHQL_INPUT_TYPE = Type::ID;

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
    public function resolveField(ContentInterface $content, ContentTypeField $field, FieldData $fieldData) {

        $domain = $this->domainManager->current();
        $contentManager = $domain->getContentManager();

        if($fieldData instanceof FieldDataList) {
            $rowIds = [];
            foreach($fieldData->rows() as $rowData) {
                $rowIds[] = $rowData->getData();
            }

            // Find all content objects by id.
            $result = $contentManager->find($domain, $field->getReturnType(), ContentFilterInput::fromInput(['id' => array_unique($rowIds)]));
            $resultById = [];

            // The result will not include duplicates. We need to transform it for an exact result.
            foreach($result->getResult() as $content) {
                $resultById[$content->getId()] = $content;
            }

            $resolvedContent = [];
            foreach($rowIds as $rowId) {
                $resolvedContent[] = array_key_exists($rowId, $resultById) ? $resultById[$rowId] : null;
            }
            return $resolvedContent;
        }

        return $contentManager->get($domain, $field->getReturnType(), $fieldData->getData());
    }
}

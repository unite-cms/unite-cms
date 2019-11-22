<?php


namespace UniteCMS\CoreBundle\Content;

use InvalidArgumentException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use UniteCMS\CoreBundle\ContentType\ContentType;
use UniteCMS\CoreBundle\ContentType\ContentTypeField;
use UniteCMS\CoreBundle\Domain\Domain;
use UniteCMS\CoreBundle\Exception\ContentAccessDeniedException;
use UniteCMS\CoreBundle\Field\FieldTypeManager;
use UniteCMS\CoreBundle\Security\Voter\ContentFieldVoter;

class FieldDataMapper
{

    /**
     * @var FieldTypeManager
     */
    protected $fieldTypeManager;

    /**
     * @var AuthorizationCheckerInterface
     */
    protected $authorizationChecker;

    public function __construct(FieldTypeManager $fieldTypeManager, AuthorizationCheckerInterface $authorizationChecker)
    {
        $this->fieldTypeManager = $fieldTypeManager;
        $this->authorizationChecker = $authorizationChecker;
    }

    /**
     * Map input data to a content object, asking all field types how to do it.
     *
     * @param Domain $domain
     * @param ContentInterface $content
     * @param $inputData
     * @param ContentType|null $contentType
     * @param bool $bypassAccess
     *
     * @return array
     */
    public function mapToFieldData(Domain $domain, ContentInterface $content, $inputData, ContentType $contentType = null, bool $bypassAccess = false) : array {

        $inputData = empty($inputData) || !is_array($inputData) ? [] : $inputData;
        $normalizedData = $content->getData();

        if(empty($contentType)) {
            $contentType = $domain->getContentTypeManager()->getAnyType($content->getType());;
        }

        if(!$contentType) {
            throw new InvalidArgumentException(sprintf('Content Type "%s" was not found.', $content->getType()));
        }

        // Ask all defined fields on this content type for field data based on inputData.
        foreach($contentType->getFields() as $id => $field) {

            // Use present field data or null as input
            $fieldData = null;

            // If field vale is present
            if(array_key_exists($id, $inputData) && $inputData[$id] !== null) {

                // If we are not allowed to update this field throw an exception.
                if(!$bypassAccess && !$this->authorizationChecker->isGranted(ContentFieldVoter::UPDATE, new ContentField($content, $id))) {
                    throw new ContentAccessDeniedException(sprintf('You are not allowed to update field "%s".', $id));
                }

                // If we are allowed to update this field add it to fieldData
                $fieldData = $inputData[$id];
            }

            // If field data is empty AND we already have a value set, skip this field.
            if($fieldData === null && !empty($normalizedData[$id]) && !empty($normalizedData[$id]->resolveData())) {
                continue;
            }

            if($field->isListOf()) {

                $listData = [];
                foreach(($fieldData ?? []) as $rowId => $rowData) {
                    $listData[$rowId] = $this->normalizeFieldData($field, $domain, $content, $rowData, $rowId);
                }
                $normalizedData[$id] = new FieldDataList($listData);
            }

            else {
                $normalizedData[$id] = $this->normalizeFieldData($field, $domain, $content, $fieldData);
            }
        }

        return $normalizedData;
    }

    /**
     * @param ContentTypeField $field
     * @param Domain $domain
     * @param ContentInterface $content
     * @param $rowData
     * @param int|null $rowDelta
     *
     * @return FieldData|null
     */
    protected function normalizeFieldData(ContentTypeField $field, Domain $domain, ContentInterface $content, $rowData, int $rowDelta = null) {
        if(!empty($field->getUnionTypes())) {

            if(empty($rowData)) {
                return null;
            }

            $unionType = $domain->getContentTypeManager()->getUnionContentType($field->getReturnType());
            $selectedUnionType = array_keys($rowData)[0];
            $rowData = $rowData[$selectedUnionType];
            $field = $unionType->getField($selectedUnionType);
        }

        $fieldType = $this->fieldTypeManager->getFieldType($field->getType());
        return $fieldType->normalizeInputData($content, $field, $rowData, $rowDelta);
    }
}


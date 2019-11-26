<?php

namespace UniteCMS\CoreBundle\Field\Types;

use UniteCMS\CoreBundle\Content\ContentInterface;
use UniteCMS\CoreBundle\Content\FieldData;
use UniteCMS\CoreBundle\ContentType\ContentTypeField;
use voku\helper\AntiXSS;

class TextType extends AbstractFieldType
{
    const TYPE = 'text';

    public function normalizeInputData(ContentInterface $content, ContentTypeField $field, $inputData = null, int $rowDelta = null): FieldData {

        if(!empty($inputData)) {
            $inputData = (string)$inputData;

            if($field->getSettings()->get('xss_clean', true)) {
                $antiXss = new AntiXSS();
                $antiXss->removeEvilAttributes(['style']); // Allow inline styles, we need this for example for CKEditor's align feature.
                $inputData = $antiXss->xss_clean($inputData);
            }

            if($field->getSettings()->get('escape', true)) {
                $inputData = htmlspecialchars($inputData, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            }
        }

        return parent::normalizeInputData($content, $field, $inputData, $rowDelta);
    }
}

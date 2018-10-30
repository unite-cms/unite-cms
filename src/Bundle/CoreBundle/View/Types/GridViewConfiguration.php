<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 30.10.18
 * Time: 09:24
 */

namespace UniteCMS\CoreBundle\View\Types;


use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\BooleanNodeDefinition;
use UniteCMS\CoreBundle\Entity\FieldableField;

class GridViewConfiguration extends TableViewConfiguration
{
    protected function addDefaultFields($v) : array {
        $defaultTextField = $this->fieldable->getFields()
            ->filter(
                function (FieldableField $field) {
                    return in_array($field->getType(), ['text', 'email']);
                }
            )->map(
                function (FieldableField $field) {
                    return $field->getIdentifier();
                }
            )->first();

        if($defaultTextField) {
            $v['fields'] = [
                $defaultTextField,
                'updated' => ['meta' => true],
            ];
        } else {
            $v['fields'] = [
                'id',
                'updated' => ['meta' => true],
            ];
        }

        if (!empty($v['sort']['field']) && isset($v['fields'][$v['sort']['field']])) {
            unset($v['fields'][$v['sort']['field']]);
        }
        return $v['fields'];
    }

    protected function appendFieldsNode() : ArrayNodeDefinition {
        $node = parent::appendFieldsNode();
        $metaFlag = new BooleanNodeDefinition('meta');
        $metaFlag->treatNullLike(false);
        $node->getChildNodeDefinitions()[""]->append($metaFlag);
        return $node;
    }
}
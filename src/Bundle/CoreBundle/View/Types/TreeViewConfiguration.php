<?php
/**
 * Created by PhpStorm.
 * User: franzwilding
 * Date: 30.10.18
 * Time: 09:24
 */

namespace UniteCMS\CoreBundle\View\Types;

use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use UniteCMS\CoreBundle\Entity\ContentTypeField;
use UniteCMS\CoreBundle\Field\Types\ReferenceOfFieldType;

class TreeViewConfiguration extends TableViewConfiguration
{
    /**
     * Generates the configuration tree builder.
     *
     * @return TreeBuilder The tree builder
     */
    public function getConfigTreeBuilder()
    {
        // Most of this tree builder is copied from parent::getConfigTreeBuilder().
        $treeBuilder = new TreeBuilder('settings');
        $treeBuilder->getRootNode()

            ->beforeNormalization()
                ->always(\Closure::fromCallable([$this, 'normalizeConfig']))
            ->end()

            ->children()
                ->append($this->appendChildrenFieldNode())
                ->append($this->appendFieldsNode())
                ->append($this->appendFilterNode())
                ->append($this->appendSortNode())
            ->end();

        return $treeBuilder;
    }

    protected function throwChildrenFieldException($message) {
        $exception = new InvalidConfigurationException($message);
        $exception->setPath('children_field');
        return $exception;
    }

    /**
     * Returns the config definition for the children_field option.
     */
    protected function appendChildrenFieldNode() : NodeDefinition {
        $treeBuilder = new TreeBuilder('children_field', 'scalar');
        return $treeBuilder->getRootNode()
            ->isRequired()
            ->validate()->always(\Closure::fromCallable([$this, 'validateChildrenField']))->end()
        ;
    }

    protected function validateChildrenField($identifier) {

        /**
         * @var ContentTypeField $field
         */
        $field = $this->fieldable->getFields()->get($identifier);

        if(!$field) {
            throw $this->throwChildrenFieldException(sprintf('Field "%s" does not exist.', $identifier));
        }

        if($field->getType() !== ReferenceOfFieldType::getType()) {
            throw $this->throwChildrenFieldException(sprintf('Field "%s" is of type "%s" but must be of type "%s".', $identifier, $field->getType(), ReferenceOfFieldType::getType()));
        }

        if($field->getSettings()->content_type !== $this->fieldable->getRootEntity()->getIdentifier() || $field->getSettings()->domain !== $this->fieldable->getRootEntity()->getDomain()->getIdentifier()) {
            throw $this->throwChildrenFieldException(sprintf('Field "%s" must reference itself.', $identifier));
        }

        return $identifier;
    }
}
<?php


namespace UniteCMS\CoreBundle\GraphQL\Resolver\Field;

use UniteCMS\CoreBundle\Content\ContentField;
use UniteCMS\CoreBundle\Content\ContentInterface;
use UniteCMS\CoreBundle\Content\ContentResultInterface;
use UniteCMS\CoreBundle\Content\FieldData;
use UniteCMS\CoreBundle\Content\FieldDataList;
use UniteCMS\CoreBundle\Content\RevisionContent;
use UniteCMS\CoreBundle\Domain\DomainManager;
use UniteCMS\CoreBundle\Field\FieldTypeManager;
use GraphQL\Language\AST\ObjectTypeDefinitionNode;
use GraphQL\Type\Definition\ResolveInfo;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use UniteCMS\CoreBundle\Security\User\UserInterface;
use UniteCMS\CoreBundle\ContentType\UserType;
use UniteCMS\CoreBundle\Security\Voter\ContentFieldVoter;

class ContentResolver implements FieldResolverInterface
{
    /**
     * @var FieldTypeManager $fieldTypeManager
     */
    protected $fieldTypeManager;

    /**
     * @var AuthorizationCheckerInterface $authorizationChecker
     */
    protected $authorizationChecker;

    /**
     * @var DomainManager $domainManager
     */
    protected $domainManager;

    public function __construct(FieldTypeManager $fieldTypeManager, AuthorizationCheckerInterface $authorizationChecker, DomainManager $domainManager)
    {
        $this->fieldTypeManager = $fieldTypeManager;
        $this->authorizationChecker = $authorizationChecker;
        $this->domainManager = $domainManager;
    }

    /**
     * @inheritDoc
     */
    public function supports(string $typeName, ObjectTypeDefinitionNode $typeDefinitionNode): bool {
        foreach($typeDefinitionNode->interfaces as $interface) {

            if($interface->name->value === 'UniteFieldable') {
                return true;
            }

            if($interface->name->value === 'UniteContentResult') {
                return true;
            }
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function resolve($value, $args, $context, ResolveInfo $info) {

        if($value instanceof ContentResultInterface) {
            switch ($info->fieldName) {
                case 'total':
                    return $value->getTotal();
                case 'result';
                    return $value->getResult();
            }
        }

        else if($value instanceof ContentInterface) {

            $contentTypeManager = $this->domainManager->current()->getContentTypeManager();
            $contentType = $contentTypeManager->getAnyType($value->getType());

            if($contentType) {
                switch ($info->fieldName) {
                    case 'id':
                        return $value->getId();
                    case '_meta':
                        // Prevent _meta on revision content (which is a sub field of _meta).
                        return ($value instanceof RevisionContent) ? null : $value;

                    case 'locale':
                        return $contentType->isTranslatable() ? $value->getLocale() : null;

                    case 'translations':

                        if(!$contentType->isTranslatable()) {
                            return [];
                        }

                        $includeSelf = empty($args['includeSelf']) ? false : $args['includeSelf'];
                        $locales = empty($args['locales']) ? null : (is_array($args['locales']) ? $args['locales'] : [$args['locales']]);
                        $translations = [];

                        if($includeSelf) {
                            $translations[] = $value;
                        }

                        return $translations + $value->getTranslations()->filter(function(ContentInterface $content) use ($includeSelf, $locales, $value, $translations) {
                            if($locales && !in_array($content->getLocale(), $locales)) {
                                return false;
                            }

                            if(!$includeSelf && $content->getId() === $value->getId()) {
                                return false;
                            }

                            if(in_array($content, $translations)) {
                                return false;
                            }

                            return true;
                        })->toArray();

                    default:

                        // Special handle user content.
                        if($value instanceof UserInterface && $contentType instanceof UserType) {
                            if($info->fieldName === 'username') {
                                return $value->getUsername();
                            }
                        }

                        // If field is not manage by unite cms.
                        if(!$field = $contentType->getField($info->fieldName)) {
                            return null;
                        }

                        // If we don't have read access for this field.
                        if(!$this->authorizationChecker->isGranted(ContentFieldVoter::READ, new ContentField($value, $info->fieldName))) {
                            return null;
                        }

                        // If field data is empty, create an empty one to pass to the field.
                        if(!$fieldData = $value->getFieldData($field->getId())) {
                            $fieldData = $field->isListOf() ? new FieldDataList() : new FieldData();
                        }

                        // If type is a list, but a single value comes from store, create a list on the fly.
                        if($field->isListOf() && !$fieldData instanceof FieldDataList) {
                            $fieldData = new FieldDataList([$fieldData]);
                        }

                        // If type is not a list, but a list is stored, get the first value.
                        if(!$field->isListOf() && $fieldData instanceof FieldDataList) {
                            $fieldData = $fieldData->resolveData(0);
                            $fieldData = empty($fieldData) ? new FieldData() : $fieldData;
                        }

                        return $this->fieldTypeManager
                            ->getFieldType($field->getType())
                            ->resolveField($value, $field, $fieldData, $args);
                }
            }
        }

        return null;
    }
}

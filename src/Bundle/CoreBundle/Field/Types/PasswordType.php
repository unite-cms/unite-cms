<?php

namespace UniteCMS\CoreBundle\Field\Types;

use GraphQL\Type\Definition\Type;
use InvalidArgumentException;
use UniteCMS\CoreBundle\Content\ContentInterface;
use UniteCMS\CoreBundle\Content\FieldData;
use UniteCMS\CoreBundle\Content\SensitiveFieldData;
use UniteCMS\CoreBundle\ContentType\ContentTypeField;
use UniteCMS\CoreBundle\Expression\SaveExpressionLanguage;
use UniteCMS\CoreBundle\Query\BaseFieldComparison;
use UniteCMS\CoreBundle\Query\BaseFieldOrderBy;
use UniteCMS\CoreBundle\Security\Encoder\FieldableUserPasswordEncoder;
use UniteCMS\CoreBundle\Security\User\UserInterface;

class PasswordType extends AbstractFieldType
{
    const TYPE = 'password';
    const GRAPHQL_INPUT_TYPE = Type::STRING;

    /**
     * @var FieldableUserPasswordEncoder $passwordEncoder
     */
    protected $passwordEncoder;

    public function __construct(FieldableUserPasswordEncoder $passwordEncoder, SaveExpressionLanguage $saveExpressionLanguage)
    {
        $this->passwordEncoder = $passwordEncoder;
        parent::__construct($saveExpressionLanguage);
    }

    /**
     * {@inheritDoc}
     */
    protected function allowedReturnTypes(ContentTypeField $field) {
        return ['NULL'];
    }

    /**
     * {@inheritDoc}
     */
    public function normalizeInputData(ContentInterface $content, ContentTypeField $field, $inputData = null, int $rowDelta = null, array $rawInputData = []) : FieldData {
        return $this->normalizePassword($content, $inputData);
    }

    /**
     * @param ContentInterface $content
     * @param string $password
     *
     * @return SensitiveFieldData
     */
    public function normalizePassword(ContentInterface $content, ?string $password = '') : SensitiveFieldData {

        if(!$content instanceof UserInterface) {
            throw new InvalidArgumentException('Password fields can only be added to UniteUser types.');
        }

        return new SensitiveFieldData(
            empty($password) ? null : $this->passwordEncoder->encodePassword($content, $password)
        );
    }

    /**
     * {@inheritDoc}
     */
    protected function resolveRowData(ContentInterface $content, ContentTypeField $field, FieldData $fieldData, array $args = []) {
        // We will never return any password information!
        return '';
    }

    /**
     * {@inheritDoc}
     */
    public function queryOrderBy(ContentTypeField $field, array $sortInput) : ?BaseFieldOrderBy {
        // We do not allow to oder by password fields.
        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function queryComparison(ContentTypeField $field, array $whereInput) : ?BaseFieldComparison {
        // We do not allow to compare password fields.
        return null;
    }
}

<?php

namespace UniteCMS\CoreBundle\Field\Types;

use InvalidArgumentException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use UniteCMS\CoreBundle\Content\ContentInterface;
use UniteCMS\CoreBundle\Content\FieldData;
use UniteCMS\CoreBundle\Content\SensitiveFieldData;
use UniteCMS\CoreBundle\ContentType\ContentTypeField;
use UniteCMS\CoreBundle\Security\Encoder\FieldableUserPasswordEncoder;
use UniteCMS\CoreBundle\Security\User\UserInterface;

class PasswordType extends AbstractFieldType
{
    const TYPE = 'password';
    const GRAPHQL_INPUT_TYPE = 'UnitePasswordInput';

    /**
     * @var FieldableUserPasswordEncoder $passwordEncoder
     */
    protected $passwordEncoder;

    public function __construct(FieldableUserPasswordEncoder $passwordEncoder)
    {
        $this->passwordEncoder = $passwordEncoder;
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
    public function normalizeInputData(ContentInterface $content, ContentTypeField $field, $inputData = null) : FieldData {

        if(!$content instanceof UserInterface) {
            throw new InvalidArgumentException('Password fields can only be added to UniteUser types.');
        }

        if($content->getId()) {
            if(empty($inputData['oldPassword'])) {
                throw new BadCredentialsException('In order to update a password field you need to pass the old password as well.');
            }

            if(!$this->passwordEncoder->isFieldPasswordValid($content, $field->getId(), $inputData['oldPassword'])) {
                throw new BadCredentialsException('Old password is not valid.');
            }
        }

        return $this->normalizePassword($content, $inputData['password']);
    }

    /**
     * @param ContentInterface $content
     * @param string $password
     *
     * @return SensitiveFieldData
     */
    public function normalizePassword(ContentInterface $content, string $password) : SensitiveFieldData {
        return new SensitiveFieldData(
            $this->passwordEncoder->encodePassword($content, $password)
        );
    }

    /**
     * {@inheritDoc}
     */
    protected function resolveRowData(ContentInterface $content, ContentTypeField $field, FieldData $fieldData) {
        // We will never return any password information!
        return '';
    }
}

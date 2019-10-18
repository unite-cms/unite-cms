<?php

namespace UniteCMS\CoreBundle\Field\Types;

use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use UniteCMS\CoreBundle\Content\ContentInterface;
use UniteCMS\CoreBundle\Content\FieldData;
use UniteCMS\CoreBundle\ContentType\ContentTypeField;
use UniteCMS\CoreBundle\User\UserInterface;

class PasswordType extends AbstractFieldType
{
    const TYPE = 'password';
    const GRAPHQL_INPUT_TYPE = 'UnitePasswordInput';

    /**
     * @var UserPasswordEncoderInterface $passwordEncoder
     */
    protected $passwordEncoder;

    public function __construct(UserPasswordEncoderInterface $passwordEncoder)
    {
        $this->passwordEncoder = $passwordEncoder;
    }

    /**
     * {@inheritDoc}
     */
    public function normalizeInputData(ContentInterface $content, ContentTypeField $field, $inputData = null) : FieldData {

        if(!$content instanceof UserInterface) {
            throw new \InvalidArgumentException('Password fields can only be added to UniteUser types.');
        }

        if($content->getId()) {
            if(!$this->passwordEncoder->isPasswordValid($content, $inputData['old_password'])) {
                throw new BadCredentialsException('Invalid old password');
            }
        }

        return new FieldData(
            $this->passwordEncoder->encodePassword($content, $inputData['password'])
        );
    }

    /**
     * {@inheritDoc}
     */
    protected function resolveRowData(ContentInterface $content, ContentTypeField $field, FieldData $fieldData) {
        return '';
    }
}

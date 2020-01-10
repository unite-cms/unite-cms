<?php


namespace UniteCMS\CoreBundle\ContentType;

use Symfony\Component\Validator\Constraints as Assert;
use UniteCMS\CoreBundle\Security\Voter\ContentVoter;
use UniteCMS\CoreBundle\Validator\GenericContentValidatorConstraint;

class ContentTypeManager
{
    /**
     * @var ContentType[] $contentTypes
     * @Assert\Valid
     */
    protected $contentTypes = [];

    /**
     * @var ContentType[] $singleContentTypes
     * @Assert\Valid
     */
    protected $singleContentTypes = [];

    /**
     * @var ContentType[] $embeddedContentTypes
     * @Assert\Valid
     */
    protected $embeddedContentTypes = [];

    /**
     * @var ContentType[] $unionContentTypes
     * @Assert\Valid
     */
    protected $unionContentTypes = [];

    /**
     * @var UserType[] $userTypes;
     * @Assert\Valid
     */
    protected $userTypes = [];

    /**
     * @var GenericContentValidatorConstraint[]
     */
    protected $genericContentConstraints = [];

    /**
     * @return ContentType[]
     */
    public function getContentTypes(): array
    {
        return $this->contentTypes;
    }

    /**
     * @param string $id
     * @return ContentType|null
     */
    public function getContentType(string $id): ?ContentType
    {
        return $this->contentTypes[$id] ?? null;
    }

    /**
     * @param ContentType $contentType
     * @return ContentTypeManager
     */
    public function registerContentType(ContentType $contentType): self
    {
        $this->contentTypes[$contentType->getId()] = $contentType;
        $this->applyGenericContentConstraints($contentType);

        // Find and generate nested union types.
        foreach($contentType->getFields() as $field) {
            if(!empty($field->getUnionTypes())) {
                $unionType = new ContentType($field->getReturnType(), $field->getReturnType(), $contentType->getPermission(ContentVoter::MUTATION));
                $this->applyGenericContentConstraints($unionType);

                foreach($field->getUnionTypes() as $type) {
                    $unionType->registerField(new ContentTypeField($type->name, $type->description ?? $type->name, $field->getType(), [], false, false, false, null, null, $type->name));
                }

                $this->unionContentTypes[$unionType->getId()] = $unionType;
            }
        }

        return $this;
    }

    /**
     * @return ContentType[]
     */
    public function getSingleContentTypes(): array
    {
        return $this->singleContentTypes;
    }

    /**
     * @param string $id
     * @return ContentType|null
     */
    public function getSingleContentType(string $id): ?ContentType
    {
        return $this->singleContentTypes[$id] ?? null;
    }

    /**
     * @param ContentType $contentType
     * @return ContentTypeManager
     */
    public function registerSingleContentType(ContentType $contentType): self
    {
        $this->singleContentTypes[$contentType->getId()] = $contentType;
        $this->applyGenericContentConstraints($contentType);
        return $this;
    }

    /**
     * @return ContentType[]
     */
    public function getEmbeddedContentTypes(): array
    {
        return $this->embeddedContentTypes;
    }

    /**
     * @param string $id
     * @return ContentType|null
     */
    public function getEmbeddedContentType(string $id): ?ContentType
    {
        return $this->embeddedContentTypes[$id] ?? null;
    }

    /**
     * @param ContentType $contentType
     * @return ContentTypeManager
     */
    public function registerEmbeddedContentType(ContentType $contentType): self
    {
        $this->embeddedContentTypes[$contentType->getId()] = $contentType;
        $this->applyGenericContentConstraints($contentType);
        return $this;
    }

    public function registerUserType(UserType $contentType) : self
    {
        $this->userTypes[$contentType->getId()] = $contentType;
        $this->applyGenericContentConstraints($contentType);
        return $this;
    }

    /**
     * @return UserType[]
     */
    public function getUserTypes(): array
    {
        return $this->userTypes;
    }

    /**
     * @param string $id
     * @return UserType|null
     */
    public function getUserType(string $id): ?UserType
    {
        return $this->userTypes[$id] ?? null;
    }

    /**
     * @return ContentType[]
     */
    public function getUnionContentTypes(): array
    {
        return $this->unionContentTypes;
    }

    /**
     * @param string $id
     * @return ContentType|null
     */
    public function getUnionContentType(string $id): ?ContentType
    {
        return $this->unionContentTypes[$id] ?? null;
    }


    public function getAnyType(string $id) : ?ContentType {
        return $this->getContentType($id) ?? $this->getSingleContentType($id) ?? $this->getEmbeddedContentType($id) ?? $this->getUnionContentType($id) ?? $this->getUserType($id);
    }

    /**
     * @return ContentType[]
     */
    public function getAllTypes() : array {
        return $this->contentTypes + $this->singleContentTypes + $this->embeddedContentTypes + $this->unionContentTypes + $this->userTypes;
    }

    /**
     * @param GenericContentValidatorConstraint[] $constraints
     * @return self
     */
    public function setGenericContentConstraints(array $constraints) : self {
        $this->genericContentConstraints = $constraints;
        return $this;
    }

    /**
     * @param ContentType $contentType
     */
    public function applyGenericContentConstraints(ContentType $contentType) : void {
        foreach($this->genericContentConstraints as $constraint) {
            if($constraint->supportsContentType($contentType)) {
                $contentType->addConstraint($constraint);
            }
        }
    }
}

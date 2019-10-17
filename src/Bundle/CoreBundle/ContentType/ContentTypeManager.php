<?php


namespace UniteCMS\CoreBundle\ContentType;

use Symfony\Component\Validator\Constraints as Assert;
use UniteCMS\CoreBundle\UserType\UserType;

class ContentTypeManager
{
    /**
     * @var ContentType[] $contentTypes
     * @Assert\Valid
     */
    protected $contentTypes = [];

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

        // Find and generate nested union types.
        foreach($contentType->getFields() as $field) {
            if(!empty($field->getUnionTypes())) {
                $unionType = new ContentType($field->getReturnType());

                foreach($field->getUnionTypes() as $type) {
                    $unionType->registerField(new ContentTypeField($type->name, $field->getType(), [], false, false, null, null, $type->name));
                }

                $this->unionContentTypes[$unionType->getId()] = $unionType;
            }
        }

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
        return $this;
    }

    public function registerUserType(UserType $contentType) : self
    {
        $this->userTypes[$contentType->getId()] = $contentType;
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
     * @return \UniteCMS\CoreBundle\ContentType\ContentType[]
     */
    public function getUnionContentTypes(): array
    {
        return $this->unionContentTypes;
    }

    /**
     * @param string $id
     * @return \UniteCMS\CoreBundle\ContentType\ContentType|null
     */
    public function getUnionContentType(string $id): ?ContentType
    {
        return $this->unionContentTypes[$id] ?? null;
    }


    public function getAnyType(string $id) : ?ContentType {
        return $this->getContentType($id) ?? $this->getEmbeddedContentType($id) ?? $this->getUnionContentType($id) ?? $this->getUserType($id);
    }

    /**
     * @return \UniteCMS\CoreBundle\ContentType\ContentType[]
     */
    public function getAllTypes() : array {
        return $this->contentTypes + $this->embeddedContentTypes + $this->unionContentTypes + $this->userTypes;
    }
}

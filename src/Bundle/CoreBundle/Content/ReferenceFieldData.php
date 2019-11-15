<?php

namespace UniteCMS\CoreBundle\Content;

use InvalidArgumentException;
use UniteCMS\CoreBundle\Domain\Domain;
use UniteCMS\CoreBundle\Exception\ContentNotFoundException;

class ReferenceFieldData extends FieldData {

    /**
     * @param Domain $domain
     * @param string $type
     * @param bool $includeDeleted
     *
     * @return mixed|null
     * @throws ContentNotFoundException
     */
    public function resolveReference(Domain $domain, string $type, bool $includeDeleted = false) : ?ContentInterface {

        if(!$id = $this->resolveData()) {
            return null;
        }

        $contentManager = null;

        if($domain->getContentTypeManager()->getContentType($type)) {
            $contentManager = $domain->getContentManager();
        }

        else if($domain->getContentTypeManager()->getUserType($type)) {
            $contentManager = $domain->getUserManager();
        }

        if(empty($contentManager)) {
            throw new InvalidArgumentException(sprintf('User or Content type "%s" was not found!', $type));
        }

        $content = $contentManager->get($domain, $type, $id, $includeDeleted);

        if(!$content) {
            throw new ContentNotFoundException();
        }

        return $content;
    }

}

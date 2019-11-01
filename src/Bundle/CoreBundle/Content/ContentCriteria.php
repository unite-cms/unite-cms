<?php

namespace UniteCMS\CoreBundle\Content;

use Doctrine\Common\Collections\Criteria;

class ContentCriteria extends Criteria
{
    protected $parameters = [];

    /**
     * @param array $contentOrderings
     * @return Criteria
     */
    public function contentOrderBy(array $contentOrderings = [])
    {
        $orderings = [];

        foreach($contentOrderings as $ordering) {
            $orderings[$ordering['field']] = $ordering['order'];
        }

        return parent::orderBy(
            $orderings
        );
    }
}

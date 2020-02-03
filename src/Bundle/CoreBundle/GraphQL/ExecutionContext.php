<?php


namespace UniteCMS\CoreBundle\GraphQL;


class ExecutionContext
{
    /**
     * @return bool
     */
    public function isBypassAccessCheck(): bool {
        return false;
    }
}

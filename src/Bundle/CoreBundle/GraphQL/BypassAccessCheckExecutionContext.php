<?php


namespace UniteCMS\CoreBundle\GraphQL;

class BypassAccessCheckExecutionContext extends ExecutionContext
{
    /**
     * @inheritDoc
     */
    public function isBypassAccessCheck(): bool {
        return true;
    }
}

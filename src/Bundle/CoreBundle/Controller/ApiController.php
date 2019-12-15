<?php


namespace UniteCMS\CoreBundle\Controller;

use UniteCMS\CoreBundle\GraphQL\SchemaManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

class ApiController extends AbstractController
{
    public function api(Request $request, SchemaManager $schemaManager, bool $uniteCMSDebug = false) {
        return $this->json(
            $schemaManager->executeRequest($request, $uniteCMSDebug)->toArray($uniteCMSDebug)
        );
    }
}

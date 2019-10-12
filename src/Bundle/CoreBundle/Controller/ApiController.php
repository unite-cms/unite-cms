<?php


namespace UniteCMS\CoreBundle\Controller;

use UniteCMS\CoreBundle\GraphQL\SchemaManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

class ApiController extends AbstractController
{
    public function api(Request $request, SchemaManager $schemaManager) {

        $result = $schemaManager->executeRequest($request, true);

        return $this->json($result, 200, [
            'Access-Control-Allow-Headers' => 'origin, content-type, accept',
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'POST, OPTIONS',
        ]);

    }
}

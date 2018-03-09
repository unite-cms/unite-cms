<?php

namespace UnitedCMS\CoreBundle\Controller;

use GraphQL\Server\Helper;
use GraphQL\Server\RequestError;
use GraphQL\Server\ServerConfig;
use GraphQL\Server\StandardServer;
use GraphQL\Type\Schema;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use UnitedCMS\CoreBundle\Entity\Domain;
use UnitedCMS\CoreBundle\Entity\Organization;


class GraphQLApiController extends Controller
{

    /**
     * @param Organization $organization
     * @param Domain $domain
     * @param Request $request
     *
     * @ParamConverter("organization", options={"mapping": {"organization": "identifier"}})
     * @ParamConverter("domain", options={"mapping": {"organization": "organization", "domain": "identifier"}})
     * @Security("is_granted(constant('UnitedCMS\\CoreBundle\\Security\\DomainVoter::VIEW'), domain)")
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Organization $organization, Domain $domain, Request $request)
    {
        $schemaTypeManager = $this->get('united.cms.graphql.schema_type_manager');
        $schema = new Schema(
            [
                'query' => $schemaTypeManager->getSchemaType('Query'),
                'mutation' => ($domain->hasContentTypes()) ? $schemaTypeManager->getSchemaType('Mutation'):NULL,
                'typeLoader' => function ($name) use ($schemaTypeManager, $domain) {
                    return $schemaTypeManager->getSchemaType($name, $domain);
                },
            ]
        );

        $server = new StandardServer(
            ServerConfig::create()->setSchema($schema)->setQueryBatching(true)->setDebug(true)
        );
        $serverHelper = new Helper();

        try {
            $result = $server->executeRequest(
                $serverHelper->parseRequestParams(
                    $request->getMethod(),
                    json_decode($request->getContent(), true),
                    $request->request->all()
                )
            );
        } catch (RequestError $e) {
            $this->get('logger')->critical($e->getMessage(), ['exception' => $e]);
            return new JsonResponse([], 500);
        }

        return new JsonResponse($result);
    }
}

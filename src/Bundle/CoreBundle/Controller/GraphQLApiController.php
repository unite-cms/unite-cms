<?php

namespace UniteCMS\CoreBundle\Controller;

use GraphQL\Error\Error;
use GraphQL\Error\FormattedError;
use GraphQL\Server\Helper;
use GraphQL\Server\ServerConfig;
use GraphQL\Server\StandardServer;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use UniteCMS\CoreBundle\Entity\Domain;
use UniteCMS\CoreBundle\Entity\Organization;
use UniteCMS\CoreBundle\Exception\UserErrorAtPath;

class GraphQLApiController extends Controller
{

    /**
     * @param Organization $organization
     * @param Domain $domain
     * @param Request $request
     *
     * @ParamConverter("organization", options={"mapping": {"organization": "identifier"}})
     * @ParamConverter("domain", options={"mapping": {"organization": "organization", "domain": "identifier"}})
     * @Security("is_granted(constant('UniteCMS\\CoreBundle\\Security\\Voter\\DomainVoter::VIEW'), domain)")
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Organization $organization, Domain $domain, Request $request)
    {
        $schema = $this->get('unite.cms.graphql.schema_type_manager')->createSchema($domain, 'Query', 'Mutation');
        $server = new StandardServer(ServerConfig::create()
            ->setSchema($schema)
            ->setQueryBatching(true)
            ->setDebug($this->getParameter('kernel.debug'))
            ->setContext(
                function () use ($request) {
                    return [
                        'csrf_token' => $request->headers->get('X-CSRF-TOKEN'),
                    ];
                }
            )->setErrorFormatter(
                function (Error $error) {
                    return UserErrorAtPath::createFormattedErrorFromException($error);
                }
            )
        );

        $status = 200;
        $serverHelper = new Helper();

        try {
            $result = $server->executeRequest(
                $serverHelper->parseRequestParams(
                    $request->getMethod(),
                    json_decode($request->getContent(), true),
                    $request->request->all()
                )
            );
        } catch (\Exception $e) {
            $status = 500;

            try {
                $result = ['errors' => [FormattedError::createFromException($e)]];
            } catch (\Throwable $e) {
                $result = ['errors' => 'Internal error'];
            }
        }

        return new JsonResponse($result, $status, [
            'Access-Control-Allow-Headers' => 'origin, content-type, accept',
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'POST, OPTIONS',
        ]);
    }
}

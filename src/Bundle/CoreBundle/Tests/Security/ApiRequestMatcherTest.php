<?php

namespace src\UnitedCMS\CoreBundle\Tests\Security;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use UnitedCMS\CoreBundle\Security\ApiRequestMatcher;

class ApiRequestMatcherTest extends TestCase
{
    /**
     * Test, that the request matcher matches any api route.
     */
    public function testApiRequestMatcherForApiRoute() {

        $matcher = new ApiRequestMatcher();

        $this->assertTrue($matcher->matches(Request::create('/org1/domain1/api')));
        $this->assertTrue($matcher->matches(Request::create('/org1/domain1/api/graphql')));
        $this->assertTrue($matcher->matches(Request::create('/org_1/domain_1/api/graphql')));
        $this->assertTrue($matcher->matches(Request::create('/org-1/domain-1/api/graphql')));
        $this->assertTrue($matcher->matches(Request::create('/ORG0rg-1/DOMAINdomain-1/api/graphql')));
        $this->assertTrue($matcher->matches(Request::create('/org1/domain1/api/anyotherprovider')));
        $this->assertTrue($matcher->matches(Request::create('/09AZaz/09AZaz/api')));
        $this->assertTrue($matcher->matches(Request::create('/org1/domain1/api', 'POST')));
        $this->assertTrue($matcher->matches(Request::create('/org1/domain1/api/graphql', 'POST')));
        $this->assertTrue($matcher->matches(Request::create('/org1/domain1/api/anyotherprovider', 'POST')));
        $this->assertTrue($matcher->matches(Request::create('/org1/domain1/api/graphql/a', 'POST')));
        $this->assertTrue($matcher->matches(Request::create('/org1/domain1/api/anyotherprovider/b', 'POST')));
        $this->assertTrue($matcher->matches(Request::create('/09AZaz/09AZaz/api', 'POST')));
    }

    /**
     * Test, that the request matcher will mot match any api route if a fallback flag is provided.
     */
    public function testApiRequestMatcherForApiRouteWithFallbackFlagRoute() {

        $matcher = new ApiRequestMatcher();

        $request = Request::create('/org1/domain1/api');
        $request->headers->set('Authentication-Fallback', false);
        $this->assertTrue($matcher->matches($request));

        $request = Request::create('/org1/domain1/api');
        $request->headers->set('Authentication-Fallback', 'false');
        $this->assertTrue($matcher->matches($request));

        $request = Request::create('/org1/domain1/api');
        $request->headers->set('Authentication-Fallback', 0);
        $this->assertTrue($matcher->matches($request));

        $request = Request::create('/org1/domain1/api/graphql');
        $request->headers->set('Authentication-Fallback', false);
        $this->assertTrue($matcher->matches($request));

        $request = Request::create('/org_1/domain_1/api/graphql');
        $request->headers->set('Authentication-Fallback', false);
        $this->assertTrue($matcher->matches($request));

        $request = Request::create('/org-1/domain-1/api/graphql');
        $request->headers->set('Authentication-Fallback', false);
        $this->assertTrue($matcher->matches($request));

        $request = Request::create('/ORG0rg-1/DOMAINdomain-1/api/graphql');
        $request->headers->set('Authentication-Fallback', false);
        $this->assertTrue($matcher->matches($request));

        $request = Request::create('/org1/domain1/api/anyotherprovider');
        $request->headers->set('Authentication-Fallback', false);
        $this->assertTrue($matcher->matches($request));

        $request = Request::create('/09AZaz/09AZaz/api');
        $request->headers->set('Authentication-Fallback', false);
        $this->assertTrue($matcher->matches($request));

        $request = Request::create('/org1/domain1/api', 'POST');
        $request->headers->set('Authentication-Fallback', false);
        $this->assertTrue($matcher->matches($request));

        $request = Request::create('/org1/domain1/api/graphql', 'POST');
        $request->headers->set('Authentication-Fallback', false);
        $this->assertTrue($matcher->matches($request));

        $request = Request::create('/org1/domain1/api/anyotherprovider', 'POST');
        $request->headers->set('Authentication-Fallback', false);
        $this->assertTrue($matcher->matches($request));

        $request = Request::create('/org1/domain1/api/graphql/a', 'POST');
        $request->headers->set('Authentication-Fallback', false);
        $this->assertTrue($matcher->matches($request));

        $request = Request::create('/org1/domain1/api/anyotherprovider/b', 'POST');
        $request->headers->set('Authentication-Fallback', false);
        $this->assertTrue($matcher->matches($request));

        $request = Request::create('/09AZaz/09AZaz/api', 'POST');
        $request->headers->set('Authentication-Fallback', false);
        $this->assertTrue($matcher->matches($request));




        $request = Request::create('/org1/domain1/api');
        $request->headers->set('Authentication-Fallback', true);
        $this->assertFalse($matcher->matches($request));

        $request = Request::create('/org1/domain1/api');
        $request->headers->set('Authentication-Fallback', 'true');
        $this->assertFalse($matcher->matches($request));

        $request = Request::create('/org1/domain1/api');
        $request->headers->set('Authentication-Fallback', 1);
        $this->assertFalse($matcher->matches($request));

        $request = Request::create('/org1/domain1/api/graphql');
        $request->headers->set('Authentication-Fallback', true);
        $this->assertFalse($matcher->matches($request));

        $request = Request::create('/org1/domain1/api/anyotherprovider');
        $request->headers->set('Authentication-Fallback', true);
        $this->assertFalse($matcher->matches($request));

        $request = Request::create('/09AZaz/09AZaz/api');
        $request->headers->set('Authentication-Fallback', true);
        $this->assertFalse($matcher->matches($request));

        $request = Request::create('/org1/domain1/api', 'POST');
        $request->headers->set('Authentication-Fallback', true);
        $this->assertFalse($matcher->matches($request));

        $request = Request::create('/org1/domain1/api/graphql', 'POST');
        $request->headers->set('Authentication-Fallback', true);
        $this->assertFalse($matcher->matches($request));

        $request = Request::create('/org1/domain1/api/anyotherprovider', 'POST');
        $request->headers->set('Authentication-Fallback', true);
        $this->assertFalse($matcher->matches($request));

        $request = Request::create('/org1/domain1/api/graphql/a', 'POST');
        $request->headers->set('Authentication-Fallback', true);
        $this->assertFalse($matcher->matches($request));

        $request = Request::create('/org1/domain1/api/anyotherprovider/b', 'POST');
        $request->headers->set('Authentication-Fallback', true);
        $this->assertFalse($matcher->matches($request));

        $request = Request::create('/09AZaz/09AZaz/api', 'POST');
        $request->headers->set('Authentication-Fallback', true);
        $this->assertFalse($matcher->matches($request));

    }

    /**
     * Test, that the request matcher will not match for any non api route.
     */
    public function testApiRequestMatcherForOtherRoute() {
        $matcher = new ApiRequestMatcher();

        $this->assertFalse($matcher->matches(Request::create('/org1/domain1/api1')));
        $this->assertFalse($matcher->matches(Request::create('/api')));
        $this->assertFalse($matcher->matches(Request::create('/api/domain')));
        $this->assertFalse($matcher->matches(Request::create('/domain/api')));
        $this->assertFalse($matcher->matches(Request::create('/org/domain/api1/api')));
        $this->assertFalse($matcher->matches(Request::create('/org/domain/api1/org/api')));
        $this->assertFalse($matcher->matches(Request::create('/org/domain/api1/org/domain/api')));
        $this->assertFalse($matcher->matches(Request::create('/')));
        $this->assertFalse($matcher->matches(Request::create('/org1/domain1/api1', 'POST')));
        $this->assertFalse($matcher->matches(Request::create('/api', 'POST')));
        $this->assertFalse($matcher->matches(Request::create('/api/domain', 'POST')));
        $this->assertFalse($matcher->matches(Request::create('/domain/api', 'POST')));
        $this->assertFalse($matcher->matches(Request::create('/org/domain/api1/api', 'POST')));
        $this->assertFalse($matcher->matches(Request::create('/org/domain/api1/org/api', 'POST')));
        $this->assertFalse($matcher->matches(Request::create('/org/domain/api1/org/domain/api', 'POST')));
        $this->assertFalse($matcher->matches(Request::create('/', 'POST')));
    }
}
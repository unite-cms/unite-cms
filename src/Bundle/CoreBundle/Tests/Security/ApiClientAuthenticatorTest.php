<?php

namespace src\UniteCMS\CoreBundle\Tests\Security;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use UniteCMS\CoreBundle\Security\ApiClientAuthenticator;

class ApiClientAuthenticatorTest extends TestCase
{
    /**
     * Test getting credentials from request.
     */
    public function testGettingCredentialsFromRequest() {

        $authenticator = new ApiClientAuthenticator();

        // Make sure, APIClient do not support remember_me option.
        $this->assertFalse($authenticator->supportsRememberMe());

        // There is no password, we only check the token.
        $this->assertTrue($authenticator->checkCredentials(null, $this->createMock(UserInterface::class)));

        $request = new Request();
        $this->assertNull($authenticator->getCredentials($request));

        $request = new Request();
        $request->headers->set('Authentication', 'Bearer MyToken');
        $this->assertEquals('MyToken', $authenticator->getCredentials($request));

        $request = new Request();
        $request->headers->set('Authentication', 'MyToken');
        $this->assertEquals('MyToken', $authenticator->getCredentials($request));

        $request = new Request();
        $request->headers->set('Authentication', 'Bearer Bearer');
        $this->assertEquals('Bearer', $authenticator->getCredentials($request));

        $request = new Request();
        $request->query->set('token', 'MyToken');
        $this->assertEquals('MyToken', $authenticator->getCredentials($request));
    }

    /**
     * Test getting User from UserProvider.
     */
    public function testGettingUser() {

        $userProvider = $this->createMock(UserProviderInterface::class);
        $userProvider->expects($this->any())
            ->method('loadUserByUsername')
            ->willReturn('mockResponse');

        $authenticator = new ApiClientAuthenticator();
        $this->assertNull($authenticator->getUser(null, $userProvider));
        $this->assertEquals('mockResponse', $authenticator->getUser('any', $userProvider));
    }
}

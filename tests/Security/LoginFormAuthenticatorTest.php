<?php

namespace App\Tests\Security;

use App\Security\LoginFormAuthenticator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class LoginFormAuthenticatorTest extends TestCase
{
    public function testAuthenticate()
    {
        $session = new Session(new MockArraySessionStorage());
        $request = new Request([], ['email' => 'test@example.com', 'password' => '1234', '_csrf_token' => 'token']);
        $request->setSession($session);

        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $authenticator = new LoginFormAuthenticator($urlGenerator);

        $passport = $authenticator->authenticate($request);

        $this->assertEquals('test@example.com', $session->get(\Symfony\Component\Security\Core\Security::LAST_USERNAME));
        $this->assertInstanceOf(\Symfony\Component\Security\Http\Authenticator\Passport\Passport::class, $passport);
    }

    public function testOnAuthenticationSuccessRedirectsToTargetPath()
    {
        $session = new Session(new MockArraySessionStorage());
        $firewallName = 'main';
        $session->set('_security.main.target_path', '/target');

        $request = new Request();
        $request->setSession($session);

        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator->method('generate')->willReturn('/home');

        $authenticator = new LoginFormAuthenticator($urlGenerator);

        $token = $this->createMock(TokenInterface::class);

        $response = $authenticator->onAuthenticationSuccess($request, $token, $firewallName);

        $this->assertEquals('/target', $response->getTargetUrl());
    }

    public function testOnAuthenticationSuccessRedirectsToHomeIfNoTargetPath()
    {
        $session = new Session(new MockArraySessionStorage());
        $request = new Request();
        $request->setSession($session);

        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator->method('generate')->with('home')->willReturn('/home');

        $authenticator = new LoginFormAuthenticator($urlGenerator);
        $token = $this->createMock(TokenInterface::class);

        $response = $authenticator->onAuthenticationSuccess($request, $token, 'main');

        $this->assertEquals('/home', $response->getTargetUrl());
    }

    public function testGetLoginUrl()
    {
        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator->method('generate')->with('login')->willReturn('/login');

        $authenticator = new LoginFormAuthenticator($urlGenerator);
        $request = new Request();

        $reflection = new \ReflectionClass(LoginFormAuthenticator::class);
        $method = $reflection->getMethod('getLoginUrl');
        $method->setAccessible(true);

        $url = $method->invoke($authenticator, $request);

        $this->assertEquals('/login', $url);
    }
}

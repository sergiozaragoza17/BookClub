<?php

namespace App\Tests\Controller;
use App\Controller\SecurityController;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\HttpFoundation\Response;

class SecurityControllerTest extends TestCase
{
    public function testLogin()
    {
        $authUtils = $this->createMock(AuthenticationUtils::class);

        $authException = $this->createMock(AuthenticationException::class);

        $authUtils->method('getLastAuthenticationError')->willReturn($authException);
        $authUtils->method('getLastUsername')->willReturn('testuser');

        $controller = $this->getMockBuilder(SecurityController::class)
            ->onlyMethods(['render'])
            ->getMock();

        $controller->expects($this->once())
            ->method('render')
            ->with(
                'security/login.html.twig',
                $this->callback(function ($params) {
                    return isset($params['last_username']) && $params['last_username'] === 'testuser';
                })
            )
            ->willReturn(new Response('rendered'));

        $response = $controller->login($authUtils);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals('rendered', $response->getContent());
    }


    public function testLogout()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('This method can be blank');

        $controller = new SecurityController();
        $controller->logout();
    }
}

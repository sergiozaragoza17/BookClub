<?php

namespace App\Tests\Controller;

use App\Controller\RegistrationController;
use App\Entity\User;
use App\Form\RegistrationType;
use App\Security\LoginFormAuthenticator;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;
use Symfony\Component\HttpFoundation\Response;

class RegistrationControllerTest extends TestCase
{
    public function testRegisterFormDisplay()
    {
        $request = new Request();

        $formView = new FormView();
        $form = $this->createMock(FormInterface::class);
        $form->method('handleRequest')->willReturnSelf();
        $form->method('isSubmitted')->willReturn(false);
        $form->method('createView')->willReturn($formView);

        $controller = $this->getMockBuilder(RegistrationController::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['createForm', 'render'])
            ->getMock();

        $controller->method('createForm')->willReturn($form);
        $controller->method('render')->willReturn(new Response('form_rendered'));

        $passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
        $userAuthenticator = $this->createMock(UserAuthenticatorInterface::class);
        $authenticator = $this->createMock(LoginFormAuthenticator::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);

        $response = $controller->register($request, $passwordHasher, $userAuthenticator, $authenticator, $entityManager);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals('form_rendered', $response->getContent());
    }

    public function testRegisterFormSubmission()
    {
        $request = new Request();

        $user = new User();

        $form = $this->createMock(FormInterface::class);
        $form->method('handleRequest')->willReturnSelf();
        $form->method('isSubmitted')->willReturn(true);
        $form->method('isValid')->willReturn(true);
        $form->method('get')->willReturnSelf();
        $form->method('getData')->willReturn('plainpassword');
        $form->method('getData')->willReturn('plainpassword');
        $form->method('get')->willReturnSelf();
        $form->method('getData')->willReturn('plainpassword');
        $form->method('get')->with('plainPassword')->willReturnSelf();
        $form->method('getData')->willReturn('plainpassword');

        $controller = $this->getMockBuilder(RegistrationController::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['createForm'])
            ->getMock();

        $controller->method('createForm')->willReturn($form);

        $passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
        $passwordHasher->method('hashPassword')->willReturn('hashedpassword');

        $userAuthenticator = $this->createMock(UserAuthenticatorInterface::class);
        $userAuthenticator->method('authenticateUser')->willReturn(new Response('authenticated'));

        $authenticator = $this->createMock(LoginFormAuthenticator::class);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('persist');
        $entityManager->expects($this->once())->method('flush');

        $response = $controller->register($request, $passwordHasher, $userAuthenticator, $authenticator, $entityManager);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals('authenticated', $response->getContent());
    }
}

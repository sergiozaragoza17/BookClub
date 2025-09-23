<?php

namespace App\Tests\Controller;

use App\Controller\UserController;
use App\Entity\User;
use App\Form\ChangePasswordType;
use App\Form\UserType;
use App\Repository\ReviewRepository;
use App\Repository\UserBookRepository;
use App\Service\S3Uploader;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserControllerTest extends TestCase
{
    private function createUser(): User
    {
        $user = $this->createMock(\App\Entity\User::class);
        $user->method('getId')->willReturn(1);
        $user->method('getEmail')->willReturn('test@example.com');
        return $user;
    }

    public function testViewProfile()
    {
        $user = $this->createUser();

        $reviewRepo = $this->createMock(ReviewRepository::class);
        $reviewRepo->method('getTotalReviewsApprovedByUser')->willReturn(5);

        $controller = $this->getMockBuilder(UserController::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getUser', 'render'])
            ->getMock();

        $controller->method('getUser')->willReturn($user);
        $controller->method('render')->willReturn(new Response('rendered'));

        $response = $controller->viewProfile($reviewRepo);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals('rendered', $response->getContent());
    }

    public function testViewOtherProfile()
    {
        $user = $this->createUser();
        $books = ['book1', 'book2'];

        $reviewRepo = $this->createMock(ReviewRepository::class);
        $reviewRepo->method('getTotalReviewsApprovedByUser')->willReturn(3);

        $userBookRepo = $this->createMock(UserBookRepository::class);
        $userBookRepo->method('findBy')->willReturn($books);

        $controller = $this->getMockBuilder(UserController::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['render'])
            ->getMock();

        $controller->method('render')->willReturn(new Response('rendered'));

        $response = $controller->viewOtherProfile($user, $userBookRepo, $reviewRepo);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals('rendered', $response->getContent());
    }

    public function testViewLibrary()
    {
        $user = $this->createUser();
        $books = ['book1', 'book2'];

        $userBookRepo = $this->createMock(UserBookRepository::class);
        $userBookRepo->method('findBy')->willReturn($books);

        $controller = $this->getMockBuilder(UserController::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['render'])
            ->getMock();

        $controller->method('render')->willReturn(new Response('rendered'));

        $response = $controller->viewLibrary($user, $userBookRepo);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals('rendered', $response->getContent());
    }

    public function testEditProfile()
    {
        $user = $this->createUser();
        $request = new Request();

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('flush');

        $passwordHasher = $this->createMock(UserPasswordHasherInterface::class);

        $uploader = $this->createMock(S3Uploader::class);
        $uploader->method('upload')->willReturn('https://fakeurl.com/profile.jpg');

        $formView = new FormView();
        $uploadedFile = $this->createMock(UploadedFile::class);

        $form = $this->createMock(FormInterface::class);
        $form->method('handleRequest')->willReturnSelf();
        $form->method('isSubmitted')->willReturn(true);
        $form->method('isValid')->willReturn(true);
        $form->method('get')->willReturnSelf();
        $form->method('getData')->willReturn($uploadedFile);
        $form->method('createView')->willReturn($formView);

        $controller = $this->getMockBuilder(UserController::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getUser', 'createForm', 'addFlash', 'redirectToRoute'])
            ->getMock();

        $controller->method('getUser')->willReturn($user);
        $controller->method('createForm')->willReturn($form);
        $controller->method('addFlash')->with($this->anything(), $this->anything());
        $controller->method('redirectToRoute')->willReturn(new RedirectResponse('/profile'));

        $response = $controller->editProfile($request, $entityManager, $passwordHasher, $uploader);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/profile', $response->getTargetUrl());
    }

    public function testChangePassword()
    {
        $user = $this->createUser();
        $request = new Request();

        $entityManager = $this->createMock(EntityManagerInterface::class);

        $passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
        $passwordHasher->method('isPasswordValid')->willReturn(true);
        $passwordHasher->method('hashPassword')->willReturn('hashedPassword');

        $formView = new FormView();
        $form = $this->createMock(FormInterface::class);
        $form->method('handleRequest')->willReturnSelf();
        $form->method('isSubmitted')->willReturn(true);
        $form->method('isValid')->willReturn(true);
        $form->method('get')->willReturnSelf();
        $form->method('getData')->willReturn('newPassword');
        $form->method('createView')->willReturn($formView);

        $controller = $this->getMockBuilder(UserController::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getUser', 'createForm', 'addFlash', 'redirectToRoute'])
            ->getMock();

        $controller->method('getUser')->willReturn($user);
        $controller->method('createForm')->willReturn($form);
        $controller->method('addFlash')->with($this->anything(), $this->anything());
        $controller->method('redirectToRoute')->willReturn(new RedirectResponse('/profile'));

        $response = $controller->changePassword($request, $entityManager, $passwordHasher);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/profile', $response->getTargetUrl());
    }

    public function testDeleteProfile()
    {
        $user = $this->createUser();
        $request = new Request([], ['_token' => 'validtoken']);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('remove');
        $entityManager->expects($this->once())->method('flush');

        $controller = $this->getMockBuilder(UserController::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getUser', 'isCsrfTokenValid', 'addFlash', 'redirectToRoute'])
            ->getMock();

        $controller->method('getUser')->willReturn($user);
        $controller->method('isCsrfTokenValid')->willReturn(true);
        $controller->method('addFlash')->with($this->anything(), $this->anything());
        $controller->method('redirectToRoute')->willReturn(new RedirectResponse('/login'));

        $response = $controller->deleteProfile($entityManager, $request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/login', $response->getTargetUrl());
    }
}

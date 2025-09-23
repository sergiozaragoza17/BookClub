<?php

namespace App\Tests\Controller;

use App\Controller\AdminUserController;
use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserBookRepository;
use App\Repository\UserRepository;
use App\Repository\ReviewRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

class AdminUserControllerTest extends TestCase
{
    public function testIndex()
    {
        $users = [new User(), new User()];

        $userRepo = $this->createMock(UserRepository::class);
        $userRepo->expects($this->once())
            ->method('findAll')
            ->willReturn($users);

        $controller = $this->getMockBuilder(AdminUserController::class)
            ->onlyMethods(['render'])
            ->getMock();

        $controller->expects($this->once())
            ->method('render')
            ->with('admin/users/index.html.twig', ['users' => $users])
            ->willReturn(new Response('rendered_index'));

        $response = $controller->index($userRepo);
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals('rendered_index', $response->getContent());
    }

    public function testShow()
    {
        $user = new User();
        $books = ['book1', 'book2'];
        $totalReviews = 5;

        $reviewRepo = $this->createMock(ReviewRepository::class);
        $reviewRepo->expects($this->once())
            ->method('getTotalReviewsApprovedByUser')
            ->with($user)
            ->willReturn($totalReviews);

        $userBookRepo = $this->createMock(UserBookRepository::class);
        $userBookRepo->expects($this->once())
            ->method('findBy')
            ->with(['user' => $user], ['id' => 'DESC'], 10)
            ->willReturn($books);

        $controller = $this->getMockBuilder(AdminUserController::class)
            ->onlyMethods(['render'])
            ->getMock();

        $controller->expects($this->once())
            ->method('render')
            ->with('admin/users/show.html.twig', [
                'user' => $user,
                'books' => $books,
                'totalReviews' => $totalReviews,
            ])
            ->willReturn(new Response('rendered_show'));

        $response = $controller->show($user, $userBookRepo, $reviewRepo);
        $this->assertEquals('rendered_show', $response->getContent());
    }

    public function testLibrary()
    {
        $user = new User();
        $books = ['bookA', 'bookB'];

        $userBookRepo = $this->createMock(UserBookRepository::class);
        $userBookRepo->expects($this->once())
            ->method('findBy')
            ->with(['user' => $user])
            ->willReturn($books);

        $controller = $this->getMockBuilder(AdminUserController::class)
            ->onlyMethods(['render'])
            ->getMock();

        $controller->expects($this->once())
            ->method('render')
            ->with('admin/users/library.html.twig', [
                'user' => $user,
                'books' => $books,
            ])
            ->willReturn(new Response('rendered_library'));

        $response = $controller->library($user, $userBookRepo);
        $this->assertEquals('rendered_library', $response->getContent());
    }

    public function testEditFormNotSubmitted()
    {
        $user = new User();

        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())->method('handleRequest');
        $form->method('isSubmitted')->willReturn(false);
        $form->method('createView')->willReturn(new FormView());

        $controller = $this->getMockBuilder(AdminUserController::class)
            ->onlyMethods(['createForm', 'render'])
            ->getMock();

        $controller->expects($this->once())
            ->method('createForm')
            ->with(UserType::class, $user)
            ->willReturn($form);

        $controller->expects($this->once())
            ->method('render')
            ->with('admin/users/edit.html.twig', [
                'form' => $form->createView(),
                'user' => $user
            ])
            ->willReturn(new Response('rendered_edit'));

        $request = new Request();
        $em = $this->createMock(EntityManagerInterface::class);

        $response = $controller->edit($user, $request, $em);
        $this->assertEquals('rendered_edit', $response->getContent());
    }

    public function testEditFormSubmitted()
    {
        $user = new User();

        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())->method('handleRequest');
        $form->method('isSubmitted')->willReturn(true);
        $form->method('isValid')->willReturn(true);
        $form->method('createView')->willReturn(new FormView());

        $controller = $this->getMockBuilder(AdminUserController::class)
            ->onlyMethods(['createForm', 'addFlash', 'redirectToRoute'])
            ->getMock();

        $controller->expects($this->once())
            ->method('createForm')
            ->with(UserType::class, $user)
            ->willReturn($form);

        $controller->expects($this->once())
            ->method('addFlash')
            ->with('success', 'User updated successfully.');

        $controller->expects($this->once())
            ->method('redirectToRoute')
            ->with('admin_users')
            ->willReturn(new RedirectResponse('/admin/users'));

        $request = new Request();
        $em = $this->createMock(EntityManagerInterface::class);

        $response = $controller->edit($user, $request, $em);
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/admin/users', $response->getTargetUrl());
    }

    public function testDeleteWithCsrfValid()
    {
        $user = new User();
        $reflection = new \ReflectionProperty(User::class, 'id');
        $reflection->setAccessible(true);
        $reflection->setValue($user, 1);

        $request = new Request([], ['_token' => 'valid_token']);
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())->method('remove')->with($user);
        $em->expects($this->once())->method('flush');

        $controller = $this->getMockBuilder(AdminUserController::class)
            ->onlyMethods(['isCsrfTokenValid', 'addFlash', 'redirectToRoute'])
            ->getMock();

        $controller->expects($this->once())
            ->method('isCsrfTokenValid')
            ->with('delete1', 'valid_token')
            ->willReturn(true);

        $controller->expects($this->once())
            ->method('addFlash')
            ->with('success', 'User deleted successfully.');

        $controller->expects($this->once())
            ->method('redirectToRoute')
            ->with('admin_users')
            ->willReturn(new RedirectResponse('/admin/users'));

        $response = $controller->delete($user, $request, $em);
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/admin/users', $response->getTargetUrl());
    }

    public function testDeleteWithCsrfInvalid()
    {
        $user = new User();
        $reflection = new \ReflectionProperty(User::class, 'id');
        $reflection->setAccessible(true);
        $reflection->setValue($user, 1);

        $request = new Request([], ['_token' => 'wrong_token']);
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->never())->method('remove');
        $em->expects($this->never())->method('flush');

        $controller = $this->getMockBuilder(AdminUserController::class)
            ->onlyMethods(['isCsrfTokenValid', 'addFlash', 'redirectToRoute'])
            ->getMock();

        $controller->expects($this->once())
            ->method('isCsrfTokenValid')
            ->willReturn(false);

        $controller->expects($this->once())
            ->method('redirectToRoute')
            ->with('admin_users')
            ->willReturn(new RedirectResponse('/admin/users'));

        $response = $controller->delete($user, $request, $em);
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/admin/users', $response->getTargetUrl());
    }
}

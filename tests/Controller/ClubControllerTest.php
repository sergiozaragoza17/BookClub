<?php

namespace App\Tests\Controller;

use App\Controller\ClubController;
use App\Entity\Book;
use App\Entity\Club;
use App\Entity\ClubBook;
use App\Entity\User;
use App\Repository\ClubBookRepository;
use App\Repository\ClubBookPostRepository;
use App\Repository\ClubPostRepository;
use App\Repository\ClubRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\PaginatorInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

class ClubControllerTest extends TestCase
{
    private function createUser(): User
    {
        $user = new User();
        $user->setEmail('user@test.com')->setUsername('user');
        return $user;
    }

    private function createClub(): Club
    {
        $club = new Club();
        $club->setName('Test Club')->setCreatedBy($this->createUser());
        return $club;
    }

    public function testIndex()
    {
        $clubRepo = $this->createMock(ClubRepository::class);
        $paginator = $this->createMock(PaginatorInterface::class);
        $request = new Request();

        $pagination = $this->createMock(PaginationInterface::class);
        $paginator->method('paginate')->willReturn($pagination);

        $controller = $this->getMockBuilder(ClubController::class)
            ->onlyMethods(['render'])
            ->getMock();

        $controller->method('render')
            ->willReturn(new Response('rendered'));

        $response = $controller->index($clubRepo, $request, $paginator);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals('rendered', $response->getContent());
    }

    public function testMyClubsRedirectIfNotLoggedIn()
    {
        $clubRepo = $this->createMock(ClubRepository::class);
        $paginator = $this->createMock(PaginatorInterface::class);
        $request = new Request();

        $controller = $this->getMockBuilder(ClubController::class)
            ->onlyMethods(['getUser', 'redirectToRoute'])
            ->getMock();

        $controller->method('getUser')->willReturn(null);
        $controller->expects($this->once())
            ->method('redirectToRoute')
            ->willReturn(new RedirectResponse('/login'));

        $response = $controller->myClubs($clubRepo, $request, $paginator);
        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    public function testNewClub()
    {
        $user = $this->createUser();
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('persist');
        $entityManager->expects($this->once())->method('flush');

        $request = new Request();

        $formView = new FormView();
        $form = $this->createMock(FormInterface::class);
        $form->method('handleRequest')->willReturnSelf();
        $form->method('isSubmitted')->willReturn(true);
        $form->method('isValid')->willReturn(true);
        $form->method('createView')->willReturn($formView);

        $controller = $this->getMockBuilder(ClubController::class)
            ->onlyMethods(['getUser', 'createForm', 'redirectToRoute'])
            ->getMock();

        $controller->method('getUser')->willReturn($user);
        $controller->method('createForm')->willReturn($form);
        $controller->method('redirectToRoute')->willReturn(new RedirectResponse('/club'));

        $response = $controller->new($request, $entityManager);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/club', $response->getTargetUrl());
    }

    public function testShow()
    {
        $club = $this->createClub();

        $clubBookRepo = $this->createMock(ClubBookRepository::class);
        $clubBookRepo->method('findBy')->willReturn([]);

        $controller = $this->getMockBuilder(ClubController::class)
            ->onlyMethods(['render'])
            ->getMock();

        $controller->expects($this->once())
            ->method('render')
            ->with('club/show.html.twig', $this->arrayHasKey('clubBooks'))
            ->willReturn(new Response('rendered'));

        $response = $controller->show($club, $clubBookRepo);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals('rendered', $response->getContent());
    }

    public function testEdit()
    {
        $club = $this->createClub();
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $request = new Request();

        $formView = new FormView();
        $form = $this->createMock(FormInterface::class);
        $form->method('handleRequest')->willReturnSelf();
        $form->method('isSubmitted')->willReturn(false);
        $form->method('createView')->willReturn($formView);

        $controller = $this->getMockBuilder(ClubController::class)
            ->onlyMethods(['createForm', 'renderForm', 'redirectToRoute'])
            ->getMock();

        $controller->method('createForm')->willReturn($form);
        $controller->method('renderForm')->willReturn(new Response('rendered'));
        $controller->method('redirectToRoute')->willReturn(new RedirectResponse('/club'));

        $response = $controller->edit($request, $club, $entityManager);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals('rendered', $response->getContent());
    }

    public function testAddBook()
    {
        $user = $this->createUser();
        $club = $this->createClub();
        $club->addMember($user);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $clubBookRepo = $this->createMock(ClubBookRepository::class);
        $clubBookRepo->method('findOneBy')->willReturn(null);

        $request = new Request();
        $formView = new FormView();

        $form = $this->createMock(FormInterface::class);
        $form->method('handleRequest')->willReturnSelf();
        $form->method('isSubmitted')->willReturn(false);
        $form->method('isValid')->willReturn(true);
        $form->method('createView')->willReturn($formView);

        $controller = $this->getMockBuilder(ClubController::class)
            ->onlyMethods(['getUser', 'createForm', 'renderForm', 'addFlash', 'redirectToRoute', 'isGranted'])
            ->getMock();

        $controller->method('getUser')->willReturn($user);
        $controller->method('isGranted')->willReturn(true);
        $controller->method('createForm')->willReturn($form);
        $controller->method('renderForm')->willReturn(new Response('rendered'));
        $controller->method('redirectToRoute')->willReturn(new RedirectResponse('/club/1'));
        $controller->expects($this->any())->method('addFlash');

        $response = $controller->addBook($club, $request, $entityManager, $clubBookRepo);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals('rendered', $response->getContent());
    }

    public function testRemoveBook()
    {
        $user = $this->createUser();

        $club = $this->getMockBuilder(Club::class)
            ->onlyMethods(['getCreatedBy', 'getId', 'getMembers'])
            ->getMock();
        $club->method('getCreatedBy')->willReturn($user);
        $club->method('getId')->willReturn(1);
        $club->method('getMembers')->willReturn(new ArrayCollection([$user]));

        $book = new Book();
        $book->setTitle('Test Book');

        $clubBook = $this->getMockBuilder(ClubBook::class)
            ->onlyMethods(['getId'])
            ->getMock();
        $clubBook->method('getId')->willReturn(1);

        $clubBookRepo = $this->createMock(ClubBookRepository::class);
        $clubBookRepo->method('findOneBy')->willReturn($clubBook);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('remove')->with($clubBook);
        $entityManager->expects($this->once())->method('flush');

        $request = new Request([], ['_token' => 'token']);

        $controller = $this->getMockBuilder(ClubController::class)
            ->onlyMethods(['getUser', 'isCsrfTokenValid', 'addFlash', 'redirectToRoute', 'isGranted'])
            ->getMock();

        $controller->method('getUser')->willReturn($user);
        $controller->method('isCsrfTokenValid')->willReturn(true);
        $controller->method('isGranted')->willReturn(true);

        $controller->expects($this->any())
            ->method('addFlash')
            ->with($this->anything(), $this->anything());

        $controller->method('redirectToRoute')
            ->willReturn(new RedirectResponse('/club/1'));

        $response = $controller->removeBook($club, $book, $clubBookRepo, $entityManager, $request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/club/1', $response->getTargetUrl());
    }

    public function testDelete()
    {
        $club = $this->createClub();
        $request = new Request([], ['_token' => 'token']);
        $entityManager = $this->createMock(EntityManagerInterface::class);

        $controller = $this->getMockBuilder(ClubController::class)
            ->onlyMethods(['isCsrfTokenValid', 'redirectToRoute'])
            ->getMock();

        $controller->method('isCsrfTokenValid')->willReturn(true);
        $controller->method('redirectToRoute')->willReturn(new RedirectResponse('/club'));

        $response = $controller->delete($request, $club, $entityManager);
        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    public function testJoin()
    {
        $user = $this->createUser();
        $club = $this->createClub();
        $entityManager = $this->createMock(EntityManagerInterface::class);

        $controller = $this->getMockBuilder(ClubController::class)
            ->onlyMethods(['getUser', 'addFlash', 'redirectToRoute'])
            ->getMock();

        $controller->method('getUser')->willReturn($user);
        $controller->method('redirectToRoute')->willReturn(new RedirectResponse('/club/1'));
        $controller->expects($this->once())->method('addFlash');

        $response = $controller->join($club, $entityManager);
        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    public function testLeave()
    {
        $user = $this->createUser();
        $club = $this->createClub();
        $entityManager = $this->createMock(EntityManagerInterface::class);

        $club->addMember($user);

        $controller = $this->getMockBuilder(ClubController::class)
            ->onlyMethods(['getUser', 'addFlash', 'redirectToRoute'])
            ->getMock();

        $controller->method('getUser')->willReturn($user);
        $controller->method('redirectToRoute')->willReturn(new RedirectResponse('/club/1'));
        $controller->expects($this->once())->method('addFlash');

        $response = $controller->leave($club, $entityManager);
        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    public function testClubForum()
    {
        $user = $this->createUser();
        $club = $this->createClub();
        $repo = $this->createMock(ClubPostRepository::class);
        $request = new Request();
        $entityManager = $this->createMock(EntityManagerInterface::class);

        $controller = $this->getMockBuilder(ClubController::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getUser', 'createForm', 'renderForm', 'renderView', 'addFlash', 'redirectToRoute', 'isGranted'
            ])
            ->getMock();

        $controller->method('getUser')->willReturn($user);
        $controller->method('isGranted')->willReturn(true);
        $controller->method('createForm')->willReturn($this->createMock(FormInterface::class));
        $controller->method('renderForm')->willReturn(new Response('rendered'));
        $controller->method('renderView')->willReturn('<html>mocked</html>');
        $controller->method('redirectToRoute')->willReturn(new RedirectResponse('/club/1'));

        $container = $this->createMock(\Psr\Container\ContainerInterface::class);
        $container->method('has')->willReturn(false);
        $controller->setContainer($container);

        $response = $controller->clubForum($club, $repo, $request, $entityManager);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertTrue($response instanceof Response || $response instanceof RedirectResponse);
    }

    public function testClubBookForum()
    {
        $user = $this->createUser();
        $club = $this->createClub();
        $book = new Book();
        $repo = $this->createMock(ClubBookPostRepository::class);
        $request = new Request();
        $entityManager = $this->createMock(EntityManagerInterface::class);

        $controller = $this->getMockBuilder(ClubController::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getUser', 'createForm', 'addFlash', 'redirectToRoute', 'isGranted', 'renderForm', 'renderView'])
            ->getMock();

        $controller->method('renderView')->willReturn('<html>mocked</html>');


        $controller->method('getUser')->willReturn($user);
        $controller->method('isGranted')->willReturn(true);
        $controller->method('redirectToRoute')->willReturn(new RedirectResponse('/club/1'));
        $controller->method('createForm')->willReturn($this->createMock(FormInterface::class));
        $controller->method('renderForm')->willReturn(new Response('rendered'));
        $container = $this->createMock(\Psr\Container\ContainerInterface::class);
        $container->method('has')->willReturn(false);
        $controller->setContainer($container);

        $response = $controller->clubBookForum($club, $book, $repo, $request, $entityManager);
        $this->assertInstanceOf(Response::class, $response);
    }
}

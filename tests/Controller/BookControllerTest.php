<?php

namespace App\Tests\Controller;

use App\Controller\BookController;
use App\Entity\Book;
use App\Entity\User;
use App\Entity\UserBook;
use App\Repository\BookRepository;
use App\Repository\UserBookRepository;
use App\Repository\ClubRepository;
use App\Repository\ClubBookRepository;
use App\Service\S3Uploader;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class BookControllerTest extends TestCase
{
    private function createUser(): User
    {
        $user = new User();
        $user->setEmail('test@example.com');
        return $user;
    }

    /** INDEX */
    public function testIndex()
    {
        $bookRepo = $this->createMock(BookRepository::class);
        $userBookRepo = $this->createMock(UserBookRepository::class);
        $paginator = $this->createMock(PaginatorInterface::class);
        $request = new Request();

        $paginationMock = $this->createMock(\Knp\Component\Pager\Pagination\PaginationInterface::class);
        $paginator->method('paginate')->willReturn($paginationMock);

        $controller = $this->getMockBuilder(BookController::class)
            ->onlyMethods(['render', 'getUser'])
            ->getMock();
        $controller->method('getUser')->willReturn($this->createUser());

        $controller->expects($this->once())
            ->method('render')
            ->with('book/index.html.twig', ['pagination' => $paginationMock, 'user' => $this->createUser()])
            ->willReturn(new Response('index'));

        $response = $controller->index($bookRepo, $userBookRepo, $request, $paginator);
        $this->assertEquals('index', $response->getContent());
    }

    /** MY BOOKS */
    public function testMyBooks()
    {
        $user = $this->createUser();
        $userBookRepo = $this->createMock(UserBookRepository::class);
        $paginator = $this->createMock(PaginatorInterface::class);
        $request = new Request();

        $paginationMock = $this->createMock(\Knp\Component\Pager\Pagination\PaginationInterface::class);
        $paginator->method('paginate')->willReturn($paginationMock);

        $controller = $this->getMockBuilder(BookController::class)
            ->onlyMethods(['render', 'getUser'])
            ->getMock();
        $controller->method('getUser')->willReturn($user);
        $controller->expects($this->once())
            ->method('render')
            ->with('book/my_books.html.twig', ['pagination' => $paginationMock, 'user' => $user])
            ->willReturn(new Response('myBooks'));

        $response = $controller->myBooks($this->createMock(BookRepository::class), $userBookRepo, $request, $paginator);
        $this->assertEquals('myBooks', $response->getContent());
    }

    /** NEW */
    public function testNewBook()
    {
        $request = new Request();
        $controller = $this->getMockBuilder(BookController::class)
            ->onlyMethods(['createForm', 'renderForm', 'getUser', 'isGranted', 'addFlash', 'redirectToRoute'])
            ->getMock();
        $controller->method('getUser')->willReturn($this->createUser());
        $controller->expects($this->once())->method('renderForm')->willReturn(new Response('newBook'));

        $response = $controller->new($request,
            $this->createMock(EntityManagerInterface::class),
            $this->createMock(S3Uploader::class),
            $this->createMock(BookRepository::class),
            $this->createMock(UserBookRepository::class)
        );

        $this->assertEquals('newBook', $response->getContent());
    }

    /** SHOW */
    public function testShow()
    {
        $reviewsCollection = new ArrayCollection();
        $book = $this->createMock(Book::class);
        $book->method('getReviews')->willReturn($reviewsCollection);
        $book->method('getId')->willReturn(1);

        $controller = $this->getMockBuilder(BookController::class)
            ->onlyMethods(['render', 'getUser', 'createForm', 'generateUrl', 'isGranted'])
            ->getMock();

        $user = $this->createUser();
        $controller->method('getUser')->willReturn($user);

        $controller->expects($this->once())
            ->method('render')
            ->with('book/show.html.twig', $this->anything())
            ->willReturn(new Response('showBook'));

        $response = $controller->show(
            $book,
            $this->createMock(UserBookRepository::class),
            $this->createMock(ClubRepository::class),
            $this->createMock(ClubBookRepository::class)
        );

        $this->assertEquals('showBook', $response->getContent());
    }

    /** EDIT */
    public function testEditBook()
    {
        $book = new Book();
        $request = new Request();
        $controller = $this->getMockBuilder(BookController::class)
            ->onlyMethods(['createForm', 'renderForm', 'isGranted', 'addFlash', 'redirectToRoute', 'getUser'])
            ->getMock();
        $controller->method('isGranted')->willReturn(true);
        $controller->method('getUser')->willReturn($this->createUser());

        $controller->expects($this->once())->method('renderForm')->willReturn(new Response('editBook'));
        $response = $controller->edit($request, $book, $this->createMock(EntityManagerInterface::class),
            $this->createMock(S3Uploader::class), $this->createMock(UserBookRepository::class));

        $this->assertEquals('editBook', $response->getContent());
    }

    /** DELETE */
    public function testDeleteBook()
    {
        $book = $this->createMock(Book::class);
        $book->method('getId')->willReturn(1);
        $book->method('getUserBooks')->willReturn(new ArrayCollection());
        $book->method('getReviews')->willReturn(new ArrayCollection());
        $book->method('getTitle')->willReturn('Test Book');

        $request = new Request([], ['_token' => 'valid']);
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())->method('remove')->with($book);
        $em->expects($this->once())->method('flush');

        $controller = $this->getMockBuilder(BookController::class)
            ->onlyMethods(['isCsrfTokenValid', 'addFlash', 'redirectToRoute'])
            ->getMock();
        $controller->method('isCsrfTokenValid')->willReturn(true);
        $controller->expects($this->once())->method('addFlash')->with('success', 'Test Book successfully deleted.');
        $controller->expects($this->once())
            ->method('redirectToRoute')
            ->willReturn(new RedirectResponse('/book'));

        $response = $controller->delete($request, $book, $em);
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/book', $response->getTargetUrl());
    }

    /** REMOVE */
    public function testRemoveBook()
    {
        $book = $this->createMock(Book::class);
        $book->method('getTitle')->willReturn('Test Book');
        $user = $this->createUser();

        $userBook = new UserBook();
        $userBook->setUser($user)->setBook($book);

        $userBookRepo = $this->createMock(UserBookRepository::class);
        $userBookRepo->method('findOneBy')->willReturn($userBook);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())->method('remove')->with($userBook);
        $em->expects($this->once())->method('flush');

        $request = new Request([], ['_token' => 'token']);
        $controller = $this->getMockBuilder(BookController::class)
            ->onlyMethods(['getUser', 'isCsrfTokenValid', 'addFlash', 'redirectToRoute'])
            ->getMock();
        $controller->method('getUser')->willReturn($user);
        $controller->method('isCsrfTokenValid')->willReturn(true);
        $controller->expects($this->once())->method('addFlash')->with('success', $this->anything());
        $controller->expects($this->once())
            ->method('redirectToRoute')
            ->willReturn(new RedirectResponse('/book'));

        $response = $controller->remove($request, $book, $em, $userBookRepo);
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/book', $response->getTargetUrl());
    }

    /** ADD TO LIBRARY */
    public function testAddToLibraryBook()
    {
        $book = new Book();
        $book->setTitle('Library Book');
        $user = $this->createUser();

        $userBookRepo = $this->createMock(UserBookRepository::class);
        $userBookRepo->method('findOneBy')->willReturn(null);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())->method('persist');
        $em->expects($this->once())->method('flush');

        $request = new Request([], ['status' => 'pending']);
        $request->headers->set('referer', '/prev');

        $controller = $this->getMockBuilder(BookController::class)
            ->onlyMethods(['getUser', 'addFlash', 'redirect'])
            ->getMock();
        $controller->method('getUser')->willReturn($user);
        $controller->expects($this->once())->method('addFlash')->with('success', 'Library Book added to your library!');
        $controller->expects($this->once())
            ->method('redirect')
            ->willReturn(new RedirectResponse('/book'));
        $response = $controller->addToLibrary($book, $request, $em, $userBookRepo);
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/book', $response->getTargetUrl());
    }
}

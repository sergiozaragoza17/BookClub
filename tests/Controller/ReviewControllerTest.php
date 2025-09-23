<?php

namespace App\Tests\Controller;

use App\Controller\ReviewController;
use App\Entity\Book;
use App\Entity\Club;
use App\Entity\Review;
use App\Entity\User;
use App\Form\ReviewType;
use App\Repository\ClubBookRepository;
use App\Repository\ClubRepository;
use App\Repository\UserBookRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

class ReviewControllerTest extends TestCase
{
    private function createUser(): object
    {
        $user = $this->getMockBuilder(User::class)
            ->disableOriginalConstructor()
            ->getMock();
        return $user;
    }

    private function createBook(): Book
    {
        $book = $this->getMockBuilder(Book::class)
            ->disableOriginalConstructor()
            ->getMock();
        $book->method('getId')->willReturn(1);
        return $book;
    }

    private function createClub(): Club
    {
        $club = $this->getMockBuilder(Club::class)
            ->disableOriginalConstructor()
            ->getMock();
        $club->method('getId')->willReturn(1);
        $club->method('getMembers')->willReturn(new ArrayCollection([$this->createUser()]));
        return $club;
    }

    public function testNewReview()
    {
        $user = $this->createUser();
        $book = $this->createBook();
        $request = new Request();

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('persist');
        $entityManager->expects($this->once())->method('flush');

        $formView = new FormView();
        $form = $this->createMock(FormInterface::class);
        $form->method('handleRequest')->willReturnSelf();
        $form->method('isSubmitted')->willReturn(true);
        $form->method('isValid')->willReturn(true);
        $form->method('createView')->willReturn($formView);

        $controller = $this->getMockBuilder(ReviewController::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getUser', 'createForm', 'addFlash', 'redirectToRoute'])
            ->getMock();

        $controller->method('getUser')->willReturn($user);
        $controller->method('createForm')->willReturn($form);
        $controller->method('redirectToRoute')->willReturn(new RedirectResponse('/book/1'));
        $controller->expects($this->once())->method('addFlash');

        $response = $controller->new($request, $book, $entityManager);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/book/1', $response->getTargetUrl());
    }

    public function testNewClubReview()
    {
        $user = $this->createUser();
        $book = $this->createBook();
        $club = $this->createClub();
        $request = new Request();

        $form = $this->createMock(FormInterface::class);
        $form->method('handleRequest')->willReturnSelf();
        $form->method('isSubmitted')->willReturn(true);
        $form->method('isValid')->willReturn(true);
        $form->method('createView')->willReturn(new FormView());

        $controller = $this->getMockBuilder(ReviewController::class)
            ->onlyMethods(['getUser', 'createForm', 'addFlash', 'redirectToRoute'])
            ->getMock();

        $controller->method('getUser')->willReturn($user);
        $controller->method('createForm')->willReturn($form);
        $controller->method('addFlash');
        $controller->method('redirectToRoute')->willReturn(new RedirectResponse('/book/1'));

        $response = $controller->newClubReview(
            $request,
            $book,
            $club,
            $this->createMock(EntityManagerInterface::class),
            $this->createMock(ClubBookRepository::class),
            $this->createMock(ClubRepository::class),
            $this->createMock(UserBookRepository::class)
        );

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/book/1', $response->getTargetUrl());
    }






    public function testEditReview()
    {
        $user = $this->createUser();
        $book = $this->createBook();
        $review = $this->getMockBuilder(Review::class)->disableOriginalConstructor()->getMock();
        $review->method('getBook')->willReturn($book);
        $review->method('getStatus')->willReturn('pending');

        $request = new Request();

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('flush');

        $form = $this->createMock(FormInterface::class);
        $form->method('handleRequest')->willReturnSelf();
        $form->method('isSubmitted')->willReturn(true);
        $form->method('isValid')->willReturn(true);

        $controller = $this->getMockBuilder(ReviewController::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['createForm', 'isGranted', 'redirectToRoute'])
            ->getMock();

        $controller->method('createForm')->willReturn($form);
        $controller->method('isGranted')->willReturn(false);
        $controller->method('redirectToRoute')->willReturn(new RedirectResponse('/book/1'));

        $response = $controller->edit($request, $review, $entityManager);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/book/1', $response->getTargetUrl());
    }

    public function testDeleteReview()
    {
        $book = $this->createBook();
        $review = $this->getMockBuilder(Review::class)->disableOriginalConstructor()->getMock();
        $review->method('getBook')->willReturn($book);
        $review->method('getId')->willReturn(1);

        $request = new Request([], ['_token' => 'valid_token']);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('remove');
        $entityManager->expects($this->once())->method('flush');

        $controller = $this->getMockBuilder(ReviewController::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isCsrfTokenValid', 'isGranted', 'redirectToRoute'])
            ->getMock();

        $controller->method('isCsrfTokenValid')->willReturn(true);
        $controller->method('isGranted')->willReturn(false);
        $controller->method('redirectToRoute')->willReturn(new RedirectResponse('/book/1'));

        $response = $controller->delete($request, $review, $entityManager);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/book/1', $response->getTargetUrl());
    }
}

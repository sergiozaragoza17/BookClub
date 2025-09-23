<?php

namespace App\Tests\Controller;

use App\Controller\HomeController;
use App\Entity\Book;
use App\Entity\Review;
use App\Entity\User;
use App\Entity\UserBook;
use App\Repository\BookRepository;
use App\Repository\ClubRepository;
use App\Repository\ReviewRepository;
use App\Repository\UserBookRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

class HomeControllerTest extends TestCase
{
    public function testHomeIndex()
    {
        $user = $this->createMock(User::class);

        $book1 = $this->createMock(Book::class);
        $book1->method('getId')->willReturn(1);
        $book2 = $this->createMock(Book::class);
        $book2->method('getId')->willReturn(2);
        $books = [$book1, $book2];

        $review1 = $this->createMock(Review::class);
        $reviews = [$review1];

        $topBooks = [$book1];

        $popularClubs = [];

        $reviewRepo = $this->createMock(ReviewRepository::class);
        $reviewRepo->method('findBy')->willReturn($reviews);

        $bookRepo = $this->createMock(BookRepository::class);
        $bookRepo->method('findBy')->willReturn($books);
        $bookRepo->method('findTopBooksByFiveStarReviews')->willReturn($topBooks);

        $userBookRepo = $this->createMock(UserBookRepository::class);

        $userBookForBook1 = $this->createMock(UserBook::class);

        $userBookRepo->method('findOneBy')
            ->willReturnCallback(function ($criteria) use ($book1, $userBookForBook1) {
                return $criteria['book'] === $book1 ? $userBookForBook1 : null;
            });

        $clubRepo = $this->createMock(ClubRepository::class);
        $clubRepo->method('getMostPopular')->willReturn($popularClubs);

        $controller = $this->getMockBuilder(HomeController::class)
            ->onlyMethods(['getUser', 'render'])
            ->getMock();

        $controller->method('getUser')->willReturn($user);
        $controller->method('render')->willReturn(new Response('rendered'));

        $response = $controller->index($reviewRepo, $bookRepo, $userBookRepo, $clubRepo);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals('rendered', $response->getContent());
    }
}

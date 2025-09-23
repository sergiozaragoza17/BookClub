<?php

namespace App\Tests\Controller;

use App\Controller\AdminReviewController;
use App\Entity\Review;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminReviewControllerTest extends TestCase
{
    public function testIndexWithoutFilter()
    {
        $request = new Request([], [], [], [], [], ['QUERY_STRING' => '']);
        $paginator = $this->createMock(PaginatorInterface::class);

        $queryBuilder = $this->getMockBuilder(\Doctrine\ORM\QueryBuilder::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();

        $reviewRepository = $this->createMock(\App\Repository\ReviewRepository::class);
        $reviewRepository->method('createQueryBuilder')->willReturn($queryBuilder);
        $pagination = $this->createMock(\Knp\Component\Pager\Pagination\PaginationInterface::class);
        $paginator->expects($this->once())
            ->method('paginate')
            ->with($queryBuilder, 1, 10)
            ->willReturn($pagination);

        $controller = $this->getMockBuilder(AdminReviewController::class)
            ->onlyMethods(['render'])
            ->getMock();

        $controller->expects($this->once())
            ->method('render')
            ->with('admin/reviews/index.html.twig', [
                'reviews' => $pagination,
                'currentStatus' => 'all'
            ])
            ->willReturn(new Response('rendered_index'));

        $response = $controller->index($reviewRepository, $request, $paginator);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals('rendered_index', $response->getContent());
    }

    public function testApproveRedirectToAdmin()
    {
        $review = $this->createMock(Review::class);
        $review->expects($this->once())->method('setStatus')->with('approved');

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())->method('flush');

        $request = new Request([], [], [], [], [], ['QUERY_STRING' => '']);
        $request->query = new ParameterBag(['redirect' => 'admin_reviews', 'status' => 'all']);

        $controller = $this->getMockBuilder(AdminReviewController::class)
            ->onlyMethods(['addFlash', 'redirectToRoute'])
            ->getMock();

        $controller->expects($this->once())
            ->method('addFlash')
            ->with('success', 'Review approved.');

        $controller->expects($this->once())
            ->method('redirectToRoute')
            ->with('admin_reviews', ['status' => 'all'])
            ->willReturn(new RedirectResponse('/fake-url'));

        $response = $controller->approve($review, $em, $request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/fake-url', $response->getTargetUrl());
    }

    public function testRejectRedirectToBookShow()
    {
        $book = $this->createMock(\App\Entity\Book::class);
        $book->method('getId')->willReturn(42);

        $review = $this->createMock(\App\Entity\Review::class);
        $review->method('setStatus')->with('rejected');
        $review->method('getBook')->willReturn($book);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())->method('flush');

        $request = new Request();
        $request->query = new ParameterBag(['redirect' => 'book_show']);

        $controller = $this->getMockBuilder(AdminReviewController::class)
            ->onlyMethods(['addFlash', 'redirectToRoute'])
            ->getMock();

        $controller->expects($this->once())
            ->method('addFlash')
            ->with('warning', 'Review rejected.');

        $controller->expects($this->once())
            ->method('redirectToRoute')
            ->with('book_show', ['id' => 42])
            ->willReturn(new RedirectResponse('/book/42'));

        $response = $controller->reject($review, $em, $request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/book/42', $response->getTargetUrl());
    }

    public function testDeleteWithCsrfValid()
    {
        $book = new \App\Entity\Book();
        $reflectionBook = new \ReflectionProperty(\App\Entity\Book::class, 'id');
        $reflectionBook->setAccessible(true);
        $reflectionBook->setValue($book, 99);

        $review = new \App\Entity\Review();
        $reflectionReview = new \ReflectionProperty(\App\Entity\Review::class, 'id');
        $reflectionReview->setAccessible(true);
        $reflectionReview->setValue($review, 1);

        $review->setBook($book);

        $em = $this->createMock(\Doctrine\ORM\EntityManagerInterface::class);
        $em->expects($this->once())->method('remove')->with($review);
        $em->expects($this->once())->method('flush');

        $request = new \Symfony\Component\HttpFoundation\Request();
        $request->request = new \Symfony\Component\HttpFoundation\ParameterBag(['_token' => 'valid_token']);
        $request->query = new \Symfony\Component\HttpFoundation\ParameterBag(['redirect' => 'book_show']);

        $controller = $this->getMockBuilder(\App\Controller\AdminReviewController::class)
            ->onlyMethods(['isCsrfTokenValid', 'addFlash', 'redirectToRoute'])
            ->getMock();

        $controller->expects($this->once())
            ->method('isCsrfTokenValid')
            ->with('delete1', 'valid_token')
            ->willReturn(true);

        $controller->expects($this->once())
            ->method('addFlash')
            ->with('success', 'Review deleted successfully.');

        $controller->expects($this->once())
            ->method('redirectToRoute')
            ->with('book_show', ['id' => 99])
            ->willReturn(new \Symfony\Component\HttpFoundation\RedirectResponse('/book/99'));

        $response = $controller->delete($review, $request, $em);

        $this->assertInstanceOf(\Symfony\Component\HttpFoundation\RedirectResponse::class, $response);
        $this->assertEquals('/book/99', $response->getTargetUrl());
    }

    public function testDeleteWithCsrfInvalid()
    {
        $review = new \App\Entity\Review();
        $reflectionReview = new \ReflectionProperty(\App\Entity\Review::class, 'id');
        $reflectionReview->setAccessible(true);
        $reflectionReview->setValue($review, 1);

        $em = $this->createMock(\Doctrine\ORM\EntityManagerInterface::class);
        $em->expects($this->never())->method('remove');
        $em->expects($this->never())->method('flush');

        $request = new \Symfony\Component\HttpFoundation\Request();
        $request->request = new \Symfony\Component\HttpFoundation\ParameterBag(['_token' => 'wrong_token']);
        $request->query = new \Symfony\Component\HttpFoundation\ParameterBag(['redirect' => 'admin_reviews']);

        $controller = $this->getMockBuilder(\App\Controller\AdminReviewController::class)
            ->onlyMethods(['isCsrfTokenValid', 'addFlash', 'redirectToRoute'])
            ->getMock();

        $controller->expects($this->once())
            ->method('isCsrfTokenValid')
            ->willReturn(false);

        $controller->expects($this->never())
            ->method('addFlash');

        $controller->expects($this->once())
            ->method('redirectToRoute')
            ->with('admin_reviews', ['status' => 'all'])
            ->willReturn(new \Symfony\Component\HttpFoundation\RedirectResponse('/fake-url'));

        $response = $controller->delete($review, $request, $em);

        $this->assertInstanceOf(\Symfony\Component\HttpFoundation\RedirectResponse::class, $response);
        $this->assertEquals('/fake-url', $response->getTargetUrl());
    }
}

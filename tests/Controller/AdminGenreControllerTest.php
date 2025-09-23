<?php

namespace App\Tests\Controller;

use App\Controller\AdminGenreController;
use App\Entity\Genre;
use App\Repository\GenreRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminGenreControllerTest extends TestCase
{
    public function testIndex()
    {
        $genreRepository = $this->createMock(GenreRepository::class);
        $genreRepository->expects($this->once())
            ->method('findAll')
            ->willReturn(['genre1', 'genre2']);

        $controller = $this->getMockBuilder(AdminGenreController::class)
            ->onlyMethods(['render', 'denyAccessUnlessGranted'])
            ->getMock();

        $controller->expects($this->once())
            ->method('denyAccessUnlessGranted')
            ->with('ROLE_ADMIN');

        $controller->expects($this->once())
            ->method('render')
            ->with('admin/genres/index.html.twig', ['genres' => ['genre1','genre2']])
            ->willReturn(new Response('rendered content'));

        $response = $controller->index($genreRepository);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals('rendered content', $response->getContent());
    }

    public function testNew()
    {
        $request = new Request();

        $form = $this->createMock(FormInterface::class);
        $form->method('handleRequest')->with($request);
        $form->method('isSubmitted')->willReturn(false);
        $form->method('isValid')->willReturn(true);
        $form->method('createView')->willReturn(new FormView());

        $formBuilder = $this->createMock(FormBuilderInterface::class);
        $formBuilder->method('add')->willReturnSelf();
        $formBuilder->method('getForm')->willReturn($form);

        $controller = $this->getMockBuilder(AdminGenreController::class)
            ->onlyMethods(['createFormBuilder', 'render', 'denyAccessUnlessGranted'])
            ->getMock();

        $controller->expects($this->once())
            ->method('denyAccessUnlessGranted')
            ->with('ROLE_ADMIN');

        $controller->expects($this->once())
            ->method('createFormBuilder')
            ->with($this->isInstanceOf(Genre::class))
            ->willReturn($formBuilder);

        $controller->expects($this->once())
            ->method('render')
            ->willReturnCallback(function($template, $params) {
                return new Response();
            });

        $response = $controller->new($request, $this->createMock(\Doctrine\ORM\EntityManagerInterface::class));

        $this->assertInstanceOf(Response::class, $response);
    }

    public function testEdit()
    {
        $request = $this->createMock(\Symfony\Component\HttpFoundation\Request::class);
        $em = $this->createMock(\Doctrine\ORM\EntityManagerInterface::class);
        $genre = new \App\Entity\Genre();

        $form = $this->createMock(\Symfony\Component\Form\FormInterface::class);
        $form->method('handleRequest')->with($request);
        $form->method('isSubmitted')->willReturn(false);
        $form->method('isValid')->willReturn(true);
        $form->method('createView')->willReturn(new \Symfony\Component\Form\FormView());

        $formBuilder = $this->createMock(\Symfony\Component\Form\FormBuilderInterface::class);
        $formBuilder->method('add')->willReturnSelf();
        $formBuilder->method('getForm')->willReturn($form);

        $controller = $this->getMockBuilder(\App\Controller\AdminGenreController::class)
            ->onlyMethods(['render', 'denyAccessUnlessGranted', 'createFormBuilder'])
            ->getMock();

        $controller->expects($this->once())
            ->method('denyAccessUnlessGranted')
            ->with('ROLE_ADMIN');

        $controller->expects($this->once())
            ->method('createFormBuilder')
            ->with($this->isInstanceOf(\App\Entity\Genre::class))
            ->willReturn($formBuilder);

        $controller->expects($this->once())
            ->method('render')
            ->willReturn(new \Symfony\Component\HttpFoundation\Response('edit form content'));

        $response = $controller->edit($request, $em, $genre);

        $this->assertInstanceOf(\Symfony\Component\HttpFoundation\Response::class, $response);
        $this->assertEquals('edit form content', $response->getContent());
    }


    public function testDeleteWithCsrfInvalid()
    {
        $em = $this->createMock(\Doctrine\ORM\EntityManagerInterface::class);

        $genre = $this->createMock(\App\Entity\Genre::class);
        $genre->method('getId')->willReturn(1);
        $genre->method('getBooks')->willReturn(new \Doctrine\Common\Collections\ArrayCollection());
        $genre->method('getClubs')->willReturn(new \Doctrine\Common\Collections\ArrayCollection());

        $request = new \Symfony\Component\HttpFoundation\Request();
        $request->request = new \Symfony\Component\HttpFoundation\ParameterBag([
            '_token' => 'wrong_token'
        ]);

        $controller = $this->getMockBuilder(\App\Controller\AdminGenreController::class)
            ->onlyMethods(['denyAccessUnlessGranted', 'isCsrfTokenValid', 'addFlash', 'redirectToRoute'])
            ->getMock();

        $controller->expects($this->once())
            ->method('denyAccessUnlessGranted')
            ->with('ROLE_ADMIN');

        $controller->expects($this->once())
            ->method('isCsrfTokenValid')
            ->with('delete_genre_1', 'wrong_token')
            ->willReturn(false);

        $controller->expects($this->once())
            ->method('addFlash')
            ->with('danger', 'Invalid CSRF token.');

        $controller->expects($this->once())
            ->method('redirectToRoute')
            ->with('admin_genre_index')
            ->willReturn(new \Symfony\Component\HttpFoundation\RedirectResponse('/fake-url'));

        $response = $controller->delete($genre, $em, $request);

        $this->assertInstanceOf(\Symfony\Component\HttpFoundation\RedirectResponse::class, $response);
        $this->assertEquals('/fake-url', $response->getTargetUrl());
    }

    public function testDeleteWithCsrfValid()
    {
        $em = $this->createMock(\Doctrine\ORM\EntityManagerInterface::class);

        $genre = $this->createMock(\App\Entity\Genre::class);
        $genre->method('getId')->willReturn(1);
        $genre->method('getBooks')->willReturn(new \Doctrine\Common\Collections\ArrayCollection());
        $genre->method('getClubs')->willReturn(new \Doctrine\Common\Collections\ArrayCollection());

        $request = new \Symfony\Component\HttpFoundation\Request();
        $request->request = new \Symfony\Component\HttpFoundation\ParameterBag([
            '_token' => 'valid_token'
        ]);

        $controller = $this->getMockBuilder(\App\Controller\AdminGenreController::class)
            ->onlyMethods(['denyAccessUnlessGranted', 'isCsrfTokenValid', 'addFlash', 'redirectToRoute'])
            ->getMock();

        $controller->expects($this->once())->method('denyAccessUnlessGranted')->with('ROLE_ADMIN');
        $controller->expects($this->once())
            ->method('isCsrfTokenValid')
            ->with('delete_genre_1', 'valid_token')
            ->willReturn(true);
        $controller->expects($this->once())->method('addFlash')->with('success', 'Genre deleted successfully!');
        $controller->expects($this->once())
            ->method('redirectToRoute')
            ->with('admin_genre_index')
            ->willReturn(new \Symfony\Component\HttpFoundation\RedirectResponse('/fake-url'));

        $em->expects($this->once())->method('remove')->with($genre);
        $em->expects($this->once())->method('flush');

        $response = $controller->delete($genre, $em, $request);

        $this->assertInstanceOf(\Symfony\Component\HttpFoundation\RedirectResponse::class, $response);
        $this->assertEquals('/fake-url', $response->getTargetUrl());
    }

    public function testDeleteWithBooksOrClubs()
    {
        $em = $this->createMock(\Doctrine\ORM\EntityManagerInterface::class);

        $genre = $this->createMock(\App\Entity\Genre::class);
        $genre->method('getId')->willReturn(1);
        $genre->method('getBooks')->willReturn(new \Doctrine\Common\Collections\ArrayCollection([new \stdClass()]));
        $genre->method('getClubs')->willReturn(new \Doctrine\Common\Collections\ArrayCollection());

        $request = new \Symfony\Component\HttpFoundation\Request();
        $request->request = new \Symfony\Component\HttpFoundation\ParameterBag([
            '_token' => 'valid_token'
        ]);

        $controller = $this->getMockBuilder(\App\Controller\AdminGenreController::class)
            ->onlyMethods(['denyAccessUnlessGranted', 'isCsrfTokenValid', 'addFlash', 'redirectToRoute'])
            ->getMock();

        $controller->expects($this->once())->method('denyAccessUnlessGranted')->with('ROLE_ADMIN');
        $controller->expects($this->once())
            ->method('isCsrfTokenValid')
            ->willReturn(true);
        $controller->expects($this->once())
            ->method('addFlash')
            ->with('danger', 'You cannot delete this genre because it is used by existing books.');
        $controller->expects($this->once())
            ->method('redirectToRoute')
            ->with('admin_genre_index')
            ->willReturn(new \Symfony\Component\HttpFoundation\RedirectResponse('/fake-url'));

        $em->expects($this->never())->method('remove');
        $em->expects($this->never())->method('flush');

        $response = $controller->delete($genre, $em, $request);

        $this->assertInstanceOf(\Symfony\Component\HttpFoundation\RedirectResponse::class, $response);
        $this->assertEquals('/fake-url', $response->getTargetUrl());
    }


}
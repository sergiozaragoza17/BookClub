<?php

namespace App\Controller;

use App\Entity\Book;
use App\Entity\User;
use App\Entity\UserBook;
use App\Form\BookType;
use App\Form\UserBookType;
use App\Repository\BookRepository;
use App\Repository\UserBookRepository;
use App\Service\S3Uploader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\VarDumper\VarDumper;

#[Route('/book')]
class BookController extends AbstractController
{
    #[Route('/', name: 'book_index', methods: ['GET'])]
    public function index(BookRepository $bookRepository, UserBookRepository $userBookRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($this->isGranted('ROLE_ADMIN')) {
            $books = $bookRepository->findAll();
        } else {
            $books = $userBookRepository->findByUser($user);

        }
        return $this->render('book/index.html.twig', [
            'books' => $books,
        ]);
    }

    #[Route('/new', name: 'book_new', methods: ['GET', 'POST'])]
    public function new(Request $request,
                        EntityManagerInterface $entityManager,
                        S3Uploader $uploader,
                        BookRepository $bookRepo,
                        UserBookRepository $userBookRepository): Response
    {
        $book = new Book();
        $form = $this->createForm(BookType::class, $book, [
            'is_admin' => $this->isGranted('ROLE_ADMIN'),
            'is_new' => true,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $existingBook = $bookRepo->findOneBy([
                'title' => $book->getTitle(),
                'author' => $book->getAuthor(),
            ]);
            if (!$existingBook) {
                /** @var UploadedFile $file */
                $file = $form->get('coverImage')->getData();
                if ($file) {
                    $url = $uploader->upload($file, 'books/');
                    $book->setCoverImage($url);
                }

                $entityManager->persist($book);
                $entityManager->flush();
                $bookToAssign = $book;
            } else {
                $bookToAssign = $existingBook;
            }
            /** @var User $user */
            $user = $this->getUser();

            $existingUserBook = $userBookRepository->findOneBy([
                'user' => $user,
                'book' => $bookToAssign,
            ]);

            if ($existingUserBook) {
                $this->addFlash('warning', 'Ya tienes este libro en tu biblioteca.');
                return $this->redirectToRoute('book_index');
            }
            if (!$this->isGranted('ROLE_ADMIN')) {

                $userBook = new UserBook();
                $userBook->setUser($user);
                $userBook->setBook($bookToAssign);
                $userBook->setStatus($request->request->get('user_book_status', 'pending'));

                $entityManager->persist($userBook);
                $entityManager->flush();
            }
            $this->addFlash('success', 'Book saved successfully.');

            return $this->redirectToRoute('book_index');
        }

        return $this->renderForm('book/new.html.twig', [
            'book' => $book,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'book_show', methods: ['GET'])]
    public function show(Book $book,  UserBookRepository $userBookRepository): Response
    {
        $user = $this->getUser();
        $userBook = $userBookRepository->findOneBy([
            'user' => $user,
            'book' => $book,
        ]);
        $reviews = $book->getReviews();

        return $this->render('book/show.html.twig', [
            'book' => $book,
            'userBook' => $userBook,
            'reviews' => $reviews,
            'user' => $user,
        ]);
    }

    #[Route('/{id}/edit', name: 'book_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Book $book,
        EntityManagerInterface $entityManager,
        S3Uploader $uploader,
        UserBookRepository $userBookRepository
    ): Response {
        if ($this->isGranted('ROLE_ADMIN')) {
            $form = $this->createForm(BookType::class, $book, [
                'is_admin' => true,
                'is_new' => false,
            ]);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                /** @var UploadedFile $file */
                $file = $form->get('coverImage')->getData();
                if ($file) {
                    $url = $uploader->upload($file, 'books/');
                    $book->setCoverImage($url);
                }

                $entityManager->flush();

                $this->addFlash('success', 'Book saved successfully.');
                return $this->redirectToRoute('book_index');
            }

            return $this->renderForm('book/edit.html.twig', [
                'book' => $book,
                'form' => $form,
            ]);
        }

        $user = $this->getUser();
        $userBook = $userBookRepository->findOneBy([
            'user' => $user,
            'book' => $book,
        ]);

        if (!$userBook) {
            throw $this->createNotFoundException('This book is not on your library.');
        }

        $formBook = $this->createForm(UserBookType::class, $userBook);
        $formBook->handleRequest($request);

        if ($formBook->isSubmitted() && $formBook->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Status updated.');
            return $this->redirectToRoute('book_index');
        }

        return $this->renderForm('book/edit.html.twig', [
            'book' => $book,
            'formBook' => $formBook,
        ]);
    }


    #[Route('/{id}', name: 'book_delete', methods: ['POST'])]
    public function delete(Request $request, Book $book, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$book->getId(), $request->request->get('_token'))) {
            foreach ($book->getUserBooks() as $userBook) {
                $entityManager->remove($userBook);
            }
            $entityManager->remove($book);
            $entityManager->flush();
            $this->addFlash('success', 'Book successfully deleted.');
        }

        return $this->redirectToRoute('book_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/remove', name: 'book_remove', methods: ['POST'])]
    public function remove(Request $request, Book $book, EntityManagerInterface $entityManager, UserBookRepository $userBookRepository): Response
    {
        $user = $this->getUser();
        $userBook = $userBookRepository->findOneBy([
            'user' => $user,
            'book' => $book,
        ]);
        if ($this->isCsrfTokenValid('remove'.$book->getId(), $request->request->get('_token'))) {
            $entityManager->remove($userBook);
            $entityManager->flush();
            $this->addFlash('success', 'Book successfully removed from library.');
        }

        return $this->redirectToRoute('book_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/add-to-library', name: 'book_add_to_library', methods: ['POST'])]
    public function addToLibrary(Book $book, Request $request, EntityManagerInterface $entityManager, UserBookRepository $userBookRepository): Response
    {
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException('You must be logged in to add books to your library.');
        }

        $existing = $userBookRepository->findOneBy([
            'user' => $user,
            'book' => $book,
        ]);

        if ($existing) {
            $this->addFlash('info', 'This book is already in your library.');
            return $this->redirectToRoute('book_show', ['id' => $book->getId()]);
        }

        $status = $request->request->get('status', 'pending'); // default 'pending'

        $userBook = new UserBook();
        $userBook->setUser($user);
        $userBook->setBook($book);
        $userBook->setStatus($status);

        $entityManager->persist($userBook);
        $entityManager->flush();

        $this->addFlash('success', 'Book added to your library!');

        return $this->redirectToRoute('book_show', ['id' => $book->getId()]);
    }
}

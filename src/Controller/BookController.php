<?php

namespace App\Controller;

use App\Entity\Book;
use App\Entity\User;
use App\Entity\UserBook;
use App\Form\BookType;
use App\Form\UserBookType;
use App\Repository\BookRepository;
use App\Repository\ClubBookRepository;
use App\Repository\ClubRepository;
use App\Repository\UserBookRepository;
use App\Service\S3Uploader;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
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

        $books = $bookRepository->findAll();

        return $this->render('book/index.html.twig', [
            'books' => $books,
            'user' => $user,
        ]);
    }

    #[Route('/my-books', name: 'my_books', methods: ['GET'])]
    public function myBooks(BookRepository $bookRepository, UserBookRepository $userBookRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $books = $userBookRepository->findByUser($user);

        return $this->render('book/my_books.html.twig', [
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
                $this->addFlash('warning', $book->getTitle().' is already in your library.');
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
            $this->addFlash('success', $book->getTitle().' saved successfully.');

            return $this->redirectToRoute('book_index');
        }

        return $this->renderForm('book/new.html.twig', [
            'book' => $book,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'book_show', methods: ['GET'])]
    public function show(Book $book,  UserBookRepository $userBookRepository, ClubRepository $clubRepository, ClubBookRepository $clubBookRepository): Response
    {
        $user = $this->getUser();
        $userBook = $userBookRepository->findOneBy([
            'user' => $user,
            'book' => $book,
        ]);
        $reviews = $book->getReviews();

        $memberClubs = $user ? $clubRepository->findByMember($user) : [];

        $validClubs = [];
        foreach ($memberClubs as $club) {
            if ($clubBookRepository->findOneBy(['club' => $club, 'book' => $book])) {
                $validClubs[] = $club;
            }
        }

        return $this->render('book/show.html.twig', [
            'book' => $book,
            'userBook' => $userBook,
            'reviews' => $reviews,
            'user' => $user,
            'validClubs' => $validClubs,
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

                $this->addFlash('success', $book->getTitle().' saved successfully.');
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
            throw $this->createNotFoundException($book->getTitle().' is not on your library.');
        }

        $formBook = $this->createForm(UserBookType::class, $userBook);
        $formBook->handleRequest($request);

        if ($formBook->isSubmitted() && $formBook->isValid()) {
            $entityManager->flush();
            $redirect = $request->query->get('redirect', 'book_index');
            $this->addFlash('success', $book->getTitle().' reading status updated.');
            if ($redirect === 'book_show') {
                return $this->redirectToRoute('book_show', ['id' => $book->getId()]);
            }

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
            foreach ($book->getReviews() as $review) {
                $entityManager->remove($review);
            }

            $entityManager->remove($book);
            $entityManager->flush();
            $this->addFlash('success', $book->getTitle().' successfully deleted.');
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
            $this->addFlash('success', $book->getTitle().' successfully removed from library.');
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

        $status = $request->request->get('status', 'pending');

        if (!$existing) {
            $userBook = new UserBook();
            $userBook->setUser($user);
            $userBook->setBook($book);
            $userBook->setStatus($status);

            $entityManager->persist($userBook);
            $entityManager->flush();
            VarDumper::dump($userBook);
        }
        $message = $existing ? $book->getTitle().' is already in your library.' : $book->getTitle().' added to your library!';

        $this->addFlash($existing ? 'info' : 'success', $message);
        return $this->redirect($request->headers->get('referer') ?? $this->generateUrl('book_index'));
    }
}

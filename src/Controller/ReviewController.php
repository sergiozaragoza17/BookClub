<?php

namespace App\Controller;

use App\Entity\Book;
use App\Entity\Club;
use App\Entity\Review;
use App\Form\ReviewType;
use App\Repository\ClubBookRepository;
use App\Repository\ClubRepository;
use App\Repository\ReviewRepository;
use App\Repository\UserBookRepository;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\VarDumper\VarDumper;

#[Route('/review')]
class ReviewController extends AbstractController
{
    #[Route('/', name: 'review_index', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function index(ReviewRepository $reviewRepository): Response
    {
        return $this->render('review/index.html.twig', [
            'reviews' => $reviewRepository->findBy(['status' => 'approved']),
        ]);
    }

    #[Route('/{book}/new', name: 'review_new', methods: ['GET', 'POST'])]
    public function new(Request $request, Book $book, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();

        $review = new Review();
        $review->setBook($book);
        $review->setUser($user);

        $form = $this->createForm(ReviewType::class, $review);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $review->setStatus('pending');
            $entityManager->persist($review);
            $entityManager->flush();

            $this->addFlash('success', 'Your review has been submitted and is pending approval.');
            return $this->redirectToRoute('book_show', ['id' => $book->getId()]);
        }
        return $this->redirectToRoute('book_show', ['id' => $book->getId()]);
    }

    #[Route('/{book}/new/club/{club}', name: 'review_new_club', methods: ['GET', 'POST'])]
    public function newClubReview(
        Request $request,
        Book $book,
        Club $club,
        EntityManagerInterface $entityManager,
        ClubBookRepository $clubBookRepository,
        ClubRepository $clubRepository,
        UserBookRepository $userBookRepository
    ): Response
    {
        $user = $this->getUser();

        if (!$club->getMembers()->contains($user) || !$clubBookRepository->findOneBy(['club' => $club, 'book' => $book])) {
            $this->addFlash('warning', 'You cannot add a review for this club.');
            return $this->redirectToRoute('book_show', ['id' => $book->getId()]);
        }

        $userBook = $userBookRepository->findOneBy([
            'user' => $user,
            'book' => $book,
        ]);
        if (!$userBook || $userBook->getStatus() !== 'finished') {
            $this->addFlash('warning', 'You must finish this book in your library before adding a club review.');
            return $this->redirectToRoute('book_show', ['id' => $book->getId()]);
        }

        $review = new Review();
        $review->setBook($book);
        $review->setUser($user);
        $review->setClub($club);

        $form = $this->createForm(ReviewType::class, $review);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $review->setStatus('pending');
            $entityManager->persist($review);
            $entityManager->flush();

            $this->addFlash('success', 'Your club review has been submitted and is pending approval.');
            return $this->redirectToRoute('book_show', ['id' => $book->getId()]);
        }
        return $this->redirectToRoute('book_show', ['id' => $book->getId()]);
    }

    #[Route('/{id}', name: 'review_show', methods: ['GET'])]
    public function show(Review $review): Response
    {
        $book = $review->getBook();
        return $this->render('review/show.html.twig', [
            'review' => $review,
            'book' => $book,
        ]);
    }

    #[Route('/{id}/edit', name: 'review_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Review $review, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ReviewType::class, $review, [
            'is_admin' => $this->isGranted('ROLE_ADMIN'),
        ]);
        if ($review->getStatus() === 'approved') {
            $form->remove('status');
        }
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $isApprovedOrRejectedBefore = in_array($review->getStatus(), ['approved', 'rejected']);
            if (!$this->isGranted('ROLE_ADMIN')) {
                $review->setStatus('pending');
                if ($isApprovedOrRejectedBefore) {
                    $review->setEdited(true);
                }
            }
            $entityManager->flush();

            if ($this->isGranted('ROLE_ADMIN')) {
                return $this->redirectToRoute('review_index', [], Response::HTTP_SEE_OTHER);
            } else {
                return $this->redirectToRoute('book_show', ['id' => $review->getBook()->getId()], Response::HTTP_SEE_OTHER);
            }
        }
        return $this->redirectToRoute('book_show', ['id' => $review->getBook()->getId()]);
    }

    #[Route('/{id}', name: 'review_delete', methods: ['POST'])]
    public function delete(Request $request, Review $review, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$review->getId(), $request->request->get('_token'))) {
            $entityManager->remove($review);
            $entityManager->flush();
        }

        if ($this->isGranted('ROLE_ADMIN')) {
            return $this->redirectToRoute('review_index', [], Response::HTTP_SEE_OTHER);
        } else {
            return $this->redirectToRoute('book_show', ['id' => $review->getBook()->getId()], Response::HTTP_SEE_OTHER);
        }
    }
}

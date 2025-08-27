<?php

namespace App\Controller;

use App\Entity\Review;
use App\Entity\User;
use App\Form\ReviewType;
use App\Form\UserType;
use App\Repository\ReviewRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/reviews')]
#[IsGranted('ROLE_ADMIN')]
class AdminReviewController extends AbstractController
{
    #[Route('', name: 'admin_reviews')]
    public function index(ReviewRepository $reviewRepository, Request $request): Response
    {
        $status = $request->query->get('status');

        if ($status === 'all') {
            $reviews = $reviewRepository->findAll();
        } else {
            $reviews = $reviewRepository->findBy(['status' => $status]);
        }
        return $this->render('admin/reviews/index.html.twig', [
            'reviews' => $reviews,
            'currentStatus' => $status,
        ]);
    }

    #[Route('/{id}', name: 'admin_review_show', methods: ['GET'])]
    public function show(Review $review): Response
    {
        return $this->render('admin/reviews/show.html.twig', [
            'review' => $review,
        ]);
    }

    #[Route('/{id}/approve', name: 'admin_review_approve')]
    public function approve(Review $review, EntityManagerInterface $em, Request $request): Response
    {
        $review->setStatus('approved');
        $em->flush();
        $this->addFlash('success', 'Review approved.');
        $redirect = $request->query->get('redirect', 'admin_reviews');
        $status = $request->query->get('status', 'all');
        if ($redirect === 'book_show') {
            return $this->redirectToRoute('book_show', ['id' => $review->getBook()->getId()]);
        }
        return $this->redirectToRoute('admin_reviews', ['status' => $status]);
    }

    #[Route('/{id}/reject', name: 'admin_review_reject')]
    public function reject(Review $review, EntityManagerInterface $em, Request  $request): Response
    {
        $review->setStatus('rejected');
        $em->flush();
        $this->addFlash('warning', 'Review rejected.');
        $redirect = $request->query->get('redirect', 'admin_reviews');
        $status = $request->query->get('status', 'all');
        if ($redirect === 'book_show') {
            return $this->redirectToRoute('book_show', ['id' => $review->getBook()->getId()]);
        }
        return $this->redirectToRoute('admin_reviews', ['status' => $status]);
    }

    #[Route('/{id}/delete', name: 'admin_review_delete', methods: ['POST'])]
    public function delete(Review $review, Request $request, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete' . $review->getId(), $request->request->get('_token'))) {
            $em->remove($review);
            $em->flush();
            $this->addFlash('success', 'Review deleted successfully.');
        }
        $redirect = $request->query->get('redirect', 'admin_reviews');
        $status = $request->query->get('status', 'all');
        if ($redirect === 'book_show') {
            return $this->redirectToRoute('book_show', ['id' => $review->getBook()->getId()]);
        }
        return $this->redirectToRoute('admin_reviews', ['status' => $status]);
    }
}
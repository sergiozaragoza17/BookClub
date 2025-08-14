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
    public function index(ReviewRepository $reviewRepo): Response
    {
        return $this->render('admin/reviews/index.html.twig', [
            'reviews' => $reviewRepo->findAll(),
        ]);
    }

    #[Route('/{id}', name: 'admin_review_show', methods: ['GET'])]
    public function show(Review $review): Response
    {
        return $this->render('admin/reviews/show.html.twig', [
            'review' => $review,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_review_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Review $review, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(ReviewType::class, $review);
        if ($review->getStatus() === 'approved') {
            $form->remove('status');
        }
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Review updated successfully.');
            return $this->redirectToRoute('admin_reviews');
        }

        return $this->renderForm('admin/reviews/edit.html.twig', [
            'review' => $review,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/approve', name: 'admin_review_approve')]
    public function approve(Review $review, EntityManagerInterface $em): Response
    {
        $review->setStatus('approved');
        $em->flush();
        $this->addFlash('success', 'Review approved.');
        return $this->redirectToRoute('admin_reviews');
    }

    #[Route('/{id}/reject', name: 'admin_review_reject')]
    public function reject(Review $review, EntityManagerInterface $em): Response
    {
        $review->setStatus('rejected');
        $em->flush();
        $this->addFlash('warning', 'Review rejected.');
        return $this->redirectToRoute('admin_reviews');
    }

    #[Route('/{id}/delete', name: 'admin_review_delete', methods: ['POST'])]
    public function delete(Review $review, Request $request, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete-review-' . $review->getId(), $request->request->get('_token'))) {
            $em->remove($review);
            $em->flush();
            $this->addFlash('success', 'Review deleted successfully.');
        }
        return $this->redirectToRoute('admin_reviews');
    }
}
<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\ReviewRepository;
use App\Repository\UserBookRepository;
use App\Repository\UserRepository;
use App\Service\S3Uploader;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/users')]
#[IsGranted('ROLE_ADMIN')]
class AdminUserController extends AbstractController
{
    #[Route('', name: 'admin_users')]
    public function index(UserRepository $userRepo): Response
    {
        return $this->render('admin/users/index.html.twig', [
            'users' => $userRepo->findAll(),
        ]);
    }

    #[Route('/{id}', name: 'admin_user_show', methods: ['GET'])]
    public function show(User $user, UserBookRepository $userBookRepository, ReviewRepository $reviewRepository): Response
    {
        $totalReviews = $reviewRepository->getTotalReviewsApprovedByUser($user);
        $books = $userBookRepository->findBy(['user' => $user], ['id' => 'DESC'], 10);
        $defaultImage = 'https://bookclub-portfolio.s3.eu-north-1.amazonaws.com/profiles/defaultProfileImage/default_profile_image.jpg';

        return $this->render('admin/users/show.html.twig', [
            'user' => $user,
            'books' => $books,
            'totalReviews' => $totalReviews,
            'defaultImage' => $defaultImage
        ]);
    }

    #[Route('/{id}/library', name: 'admin_user_library', methods: ['GET'])]
    public function library(User $user, UserBookRepository $userBookRepository): Response
    {
        $books = $userBookRepository->findBy(['user' => $user]);
        return $this->render('admin/users/library.html.twig', [
            'user' => $user,
            'books' => $books,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_user_edit')]
    public function edit(User $user, Request $request, EntityManagerInterface $em, S3Uploader $uploader): Response
    {
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            /** @var UploadedFile $file */
            $file = $form->get('profileImage')->getData();
            if ($file) {
                $url = $uploader->upload($file, 'profiles/');
                $user->setProfileImage($url);
            }

            $em->flush();
            $this->addFlash('success', 'User updated successfully.');
            return $this->redirectToRoute('admin_users');
        }

        return $this->render('admin/users/edit.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_user_delete', methods: ['POST'])]
    public function delete(User $user, Request $request, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete' . $user->getId(), $request->request->get('_token'))) {
            $em->remove($user);
            $em->flush();
            $this->addFlash('success', 'User deleted successfully.');
        }
        return $this->redirectToRoute('admin_users');
    }
}
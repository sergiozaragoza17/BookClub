<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\ChangePasswordType;
use App\Form\UserType;
use App\Repository\ReviewRepository;
use App\Repository\UserBookRepository;
use App\Service\S3Uploader;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

#[IsGranted('ROLE_USER')]
#[Route('/profile')]
class UserController extends AbstractController
{
    #[Route('', name: 'profile')]
    public function viewProfile(ReviewRepository $reviewRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $totalReviews = $reviewRepository->getTotalReviewsApprovedByUser($user);
        $defaultImage = 'https://bookclub-portfolio.s3.eu-north-1.amazonaws.com/profiles/defaultProfileImage/default_profile_image.jpg';

        return $this->render('user/view.html.twig', [
            'user' => $user,
            'totalReviews' => $totalReviews,
            'defaultImage' => $defaultImage,
        ]);
    }

    #[Route('/{id}', name: 'user_profile', methods: ['GET'])]
    public function viewOtherProfile(User $user, UserBookRepository $userBookRepository, ReviewRepository $reviewRepository): Response
    {
        $totalReviews = $reviewRepository->getTotalReviewsApprovedByUser($user);
        $books = $userBookRepository->findBy(['user' => $user], ['id' => 'DESC'], 10);
        $defaultImage = 'https://bookclub-portfolio.s3.eu-north-1.amazonaws.com/profiles/defaultProfileImage/default_profile_image.jpg';

        return $this->render('user/view_other.html.twig', [
            'user' => $user,
            'books' => $books,
            'totalReviews' => $totalReviews,
            'defaultImage' => $defaultImage
        ]);
    }

    #[Route('/{id}/library', name: 'user_library', methods: ['GET'])]
    public function viewLibrary(User $user, UserBookRepository $userBookRepository): Response
    {
        $books = $userBookRepository->findBy(['user' => $user]);
        return $this->render('user/library.html.twig', [
            'user' => $user,
            'books' => $books,
        ]);
    }

    #[Route('/{id}/edit', name: 'profile_edit')]
    public function editProfile(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        S3Uploader $uploader
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        $form = $this->createForm(UserType::class, $user, [
            'user' => $user,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            /** @var UploadedFile $file */
            $file = $form->get('profileImage')->getData();
            if ($file) {
                $url = $uploader->upload($file, 'profiles/');
                $user->setProfileImage($url);
            }

            $entityManager->flush();
            $this->addFlash('success', 'Profile updated successfully.');

            return $this->redirectToRoute('profile');
        }

        return $this->render('user/edit.html.twig', [
            'form' => $form->createView(),
            'user' => $user
        ]);
    }

    #[Route('/profile/change-password', name: 'profile_change_password')]
    public function changePassword(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $form = $this->createForm(ChangePasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $currentPassword = $form->get('currentPassword')->getData();
            $newPassword = $form->get('plainPassword')->getData();
            $confirmPassword = $form->get('confirmPassword')->getData();

            if (!$passwordHasher->isPasswordValid($user, $currentPassword)) {
                $this->addFlash('error', 'Current password is incorrect.');
                return $this->redirectToRoute('profile_change_password');
            }

            if ($newPassword !== $confirmPassword) {
                $this->addFlash('error', 'New passwords do not match.');
                return $this->redirectToRoute('profile_change_password');
            }

            $user->setPassword($passwordHasher->hashPassword($user, $newPassword));
            $entityManager->flush();

            $this->addFlash('success', 'Password changed successfully.');
            return $this->redirectToRoute('profile');
        }

        return $this->render('user/change_password.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/delete', name: 'profile_delete', methods: ['POST'])]
    public function deleteProfile(EntityManagerInterface $entityManager, Request $request): Response
    {
        $user = $this->getUser();
        if ($this->isCsrfTokenValid('delete-profile', $request->request->get('_token'))) {
            $entityManager->remove($user);
            $entityManager->flush();

            $this->addFlash('success', 'Account deleted successfully.');
        }
        return $this->redirectToRoute('login');
    }
}

<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
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
    public function viewProfile(): Response
    {
        return $this->render('user/view.html.twig', [
            'user' => $this->getUser(),
        ]);
    }

    #[Route('/edit', name: 'profile_edit')]
    public function editProfile(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
        S3Uploader $uploader
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $currentPassword = $form->get('currentPassword')->getData();
            $newPassword = $form->get('plainPassword')->getData();
            $confirmPassword = $form->get('confirmPassword')->getData();

            /** @var UploadedFile $file */
            $file = $form->get('profileImage')->getData();
            if ($file) {
                $url = $uploader->upload($file, 'profiles/');
                $user->setProfileImage($url);
            }

            // Si intenta cambiar contraseña
            if ($newPassword || $confirmPassword) {
                if (!$passwordHasher->isPasswordValid($user, $currentPassword)) {
                    $this->addFlash('error', 'Current password is incorrect.');
                    return $this->redirectToRoute('profile_edit');
                }

                if ($newPassword !== $confirmPassword) {
                    $this->addFlash('error', 'New passwords do not match.');
                    return $this->redirectToRoute('profile_edit');
                }

                $user->setPassword(
                    $passwordHasher->hashPassword($user, $newPassword)
                );
            }

            $entityManager->flush();
            $this->addFlash('success', 'Profile updated successfully.');

            return $this->redirectToRoute('profile');
        }

        return $this->render('user/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/delete', name: 'profile_delete', methods: ['POST'])]
    public function deleteProfile(EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();

        $entityManager->remove($user);
        $entityManager->flush();

        $this->addFlash('success', 'Account deleted successfully.');

        return $this->redirectToRoute('logout');
    }
}

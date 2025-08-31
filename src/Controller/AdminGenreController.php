<?php

namespace App\Controller;

use App\Entity\Genre;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/genre')]
class AdminGenreController extends AbstractController
{
    #[Route('/new', name: 'admin_genre_new')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $genre = new Genre();
        $form = $this->createFormBuilder($genre)
            ->add('name', TextType::class, [
                'label' => 'Genre Name',
                'attr' => ['placeholder' => 'Enter new genre']
            ])
            ->add('save', SubmitType::class, ['label' => 'Create'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($genre);
            $em->flush();
            $this->addFlash('success', 'Genre created successfully!');
            return $this->redirectToRoute('admin_genre_new');
        }

        return $this->render('admin/genres/new.html.twig', [
            'form' => $form->createView()
        ]);
    }
}
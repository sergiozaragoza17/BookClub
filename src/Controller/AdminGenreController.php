<?php

namespace App\Controller;

use App\Entity\Genre;
use App\Repository\GenreRepository;
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
    #[Route('/', name: 'admin_genre_index')]
    public function index(GenreRepository $genreRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $genres = $genreRepository->findAll();

        return $this->render('admin/genres/index.html.twig', [
            'genres' => $genres
        ]);
    }

    #[Route('/new', name: 'admin_genre_new')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $genre = new Genre();
        $form = $this->createFormBuilder($genre)
            ->add('name', TextType::class, [
                'label' => 'Name',
                'attr' => ['placeholder' => 'Enter new genre']
            ])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($genre);
            $em->flush();
            $this->addFlash('success', 'Genre created successfully!');
            return $this->redirectToRoute('admin_genre_index');
        }

        return $this->render('admin/genres/new.html.twig', [
            'form' => $form->createView()
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_genre_edit')]
    public function edit(Request $request, EntityManagerInterface $em, Genre $genre): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $form = $this->createFormBuilder($genre)
            ->add('name', TextType::class, [
                'label' => 'Name',
                'attr' => ['placeholder' => 'Edit genre name']
            ])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Genre updated successfully!');
            return $this->redirectToRoute('admin_genre_index');
        }

        return $this->render('admin/genres/edit.html.twig', [
            'form' => $form->createView(),
            'genre' => $genre
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_genre_delete', methods: ['POST'])]
    public function delete(Genre $genre, EntityManagerInterface $em, Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if (!$this->isCsrfTokenValid('delete_genre_' . $genre->getId(), $request->request->get('_token'))) {
            $this->addFlash('danger', 'Invalid CSRF token.');
            return $this->redirectToRoute('admin_genre_index');
        }

        if (count($genre->getBooks()) > 0) {
            $this->addFlash('danger', 'You cannot delete this genre because it is used by existing books.');
            return $this->redirectToRoute('admin_genre_index');
        }

         if (count($genre->getClubs()) > 0) {
             $this->addFlash('danger', 'You cannot delete this genre because it is used by existing clubs.');
             return $this->redirectToRoute('admin_genre_index');
         }

        $em->remove($genre);
        $em->flush();

        $this->addFlash('success', 'Genre deleted successfully!');
        return $this->redirectToRoute('admin_genre_index');
    }
}
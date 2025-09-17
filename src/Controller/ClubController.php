<?php

namespace App\Controller;

use App\Entity\Book;
use App\Entity\Club;
use App\Entity\ClubBook;
use App\Entity\ClubBookPost;
use App\Entity\ClubPost;
use App\Form\ClubBookPostType;
use App\Form\ClubBookType;
use App\Form\ClubPostType;
use App\Form\ClubType;
use App\Repository\ClubBookPostRepository;
use App\Repository\ClubBookRepository;
use App\Repository\ClubPostRepository;
use App\Repository\ClubRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\VarDumper\VarDumper;

#[Route('/club')]
class ClubController extends AbstractController
{
    #[Route('/', name: 'club_index', methods: ['GET'])]
    public function index(ClubRepository $clubRepository, Request $request, PaginatorInterface $paginator): Response
    {
        $queryBuilder = $clubRepository->createQueryBuilder('c')
            ->orderBy('c.name', 'ASC');

        $pagination = $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            12 // clubs por página
        );

        return $this->render('club/index.html.twig', [
            'pagination' => $pagination,
        ]);
    }

    #[Route('/my-clubs', name: 'my_clubs', methods: ['GET'])]
    public function myClubs(ClubRepository $clubRepository, Request $request, PaginatorInterface $paginator): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $queryBuilder = $clubRepository->createQueryBuilder('c')
            ->join('c.members', 'm')
            ->where('m = :user')
            ->setParameter('user', $user)
            ->orderBy('c.name', 'ASC');

        $pagination = $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            12
        );


        return $this->render('club/my_clubs.html.twig', [
            'pagination' => $pagination,
        ]);
    }

    #[Route('/new', name: 'club_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        $club = new Club();
        $club->setCreatedBy($user);
        $club->addMember($user);
        $form = $this->createForm(ClubType::class, $club);
        $form->handleRequest($request);


        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($club);
            $entityManager->flush();

            return $this->redirectToRoute('club_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('club/new.html.twig', [
            'club' => $club,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'club_show', methods: ['GET'])]
    public function show(Club $club, ClubBookRepository $clubBookRepository): Response
    {
        $clubBooks = $clubBookRepository->findBy(['club' => $club]);
        return $this->render('club/show.html.twig', [
            'club' => $club,
            'clubBooks' => $clubBooks,
        ]);
    }

    #[Route('/{id}/edit', name: 'club_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Club $club, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ClubType::class, $club);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('club_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('club/edit.html.twig', [
            'club' => $club,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/add-book', name: 'club_add_book', methods: ['GET', 'POST'])]
    public function addBook(
        Club $club,
        Request $request,
        EntityManagerInterface $entityManager,
        ClubBookRepository $clubBookRepo
    ): Response
    {
        if (!$club->getMembers()->contains($this->getUser()) && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('You are not a member of this club.');
        }

        $clubBook = new ClubBook();
        $form = $this->createForm(ClubBookType::class, $clubBook, ['club' => $club]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $book = $clubBook->getBook();

            if ($book->getGenre() !== $club->getGenre()) {
                $this->addFlash('danger', 'The book genre does not match the club genre.');
                return $this->redirectToRoute('club_show', ['id' => $club->getId()]);
            }

            if ($clubBookRepo->findOneBy(['club' => $club, 'book' => $book])) {
                $this->addFlash('warning', 'This book is already in the club.');
                return $this->redirectToRoute('club_show', ['id' => $club->getId()]);
            }

            $clubBook->setClub($club);
            $clubBook->setAddedBy($this->getUser());
            $clubBook->setCreatedAt(new \DateTimeImmutable());

            $entityManager->persist($clubBook);
            $entityManager->flush();

            $this->addFlash('success', 'Book added to the club.');
            return $this->redirectToRoute('club_show', ['id' => $club->getId()]);
        }

        return $this->renderForm('club/add_book.html.twig', [
            'club' => $club,
            'form' => $form,
        ]);
    }

    #[Route('/club/{club}/remove-book/{book}', name: 'club_book_remove', methods: ['POST'])]
    public function removeBook(Club $club, Book $book, ClubBookRepository $clubBookRepository, EntityManagerInterface $em, Request $request): Response
    {
        $user = $this->getUser();

        if (!$this->isGranted('ROLE_ADMIN') && $club->getCreatedBy() !== $user) {
            $this->addFlash('warning', 'Only the club creator or an admin can remove a book.');
            return $this->redirectToRoute('club_show', ['id' => $club->getId()]);
        }

        $clubBook = $clubBookRepository->findOneBy([
            'club' => $club,
            'book' => $book,
        ]);

        if (!$clubBook) {
            $this->addFlash('danger', 'Book not found in this club.');
            return $this->redirectToRoute('club_show', ['id' => $club->getId()]);
        }

        if ($this->isCsrfTokenValid('remove_book' . $clubBook->getId(), $request->request->get('_token'))) {
            $em->remove($clubBook);
            $em->flush();
            $this->addFlash('success', 'Book removed from the club.');
        }

        return $this->redirectToRoute('club_show', ['id' => $club->getId()]);
    }

    #[Route('/{id}', name: 'club_delete', methods: ['POST'])]
    public function delete(Request $request, Club $club, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$club->getId(), $request->request->get('_token'))) {
            $entityManager->remove($club);
            $entityManager->flush();
        }

        return $this->redirectToRoute('club_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/club/{id}/join', name: 'club_join', methods: ['POST'])]
    public function join(Club $club, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();

        if (!$club->getMembers()->contains($user)) {
            $club->addMember($user);
            $em->persist($club);
            $em->flush();
            $this->addFlash('success', 'You have successfully joined '.$club->getName());
        }

        return $this->redirectToRoute('club_show', ['id' => $club->getId()]);
    }

    #[Route('/club/{id}/leave', name: 'club_leave', methods: ['POST'])]
    public function leave(Club $club, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();

        if ($club->getCreatedBy() === $user) {
            $this->addFlash('warning', 'You cannot leave a club you created.');
            return $this->redirectToRoute('club_show', ['id' => $club->getId()]);
        }

        if ($club->getMembers()->contains($user)) {
            $club->removeMember($user);
            $em->persist($club);
            $em->flush();
            $this->addFlash('info', 'You have left the club.');
        }

        return $this->redirectToRoute('club_show', ['id' => $club->getId()]);
    }

    #[Route('/club/{club}/forum', name: 'club_forum')]
    public function clubForum(
        Club $club,
        ClubPostRepository $clubPostRepository,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response
    {
        $user = $this->getUser();

        if (!$this->isGranted('ROLE_ADMIN') && !$club->getMembers()->contains($user)) {
            $this->addFlash('warning', 'You must be a member of this club to view the forum.');
            return $this->redirectToRoute('club_show', ['id' => $club->getClubBooks()->first()?->getBook()?->getId()]);
        }

        $post = new ClubPost();
        $post->setClub($club);
        $post->setUser($user);

        $form = $this->createForm(ClubPostType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $parentId = $request->request->get('parent_id');
            if ($parentId) {
                $parent = $clubPostRepository->find($parentId);
                if ($parent) {
                    $post->setParent($parent);
                }
            }
            $entityManager->persist($post);
            $entityManager->flush();

            $this->addFlash('success', 'Your post has been added.');
            return $this->redirectToRoute('club_forum', ['club' => $club->getId()]);
        }

        $posts = $clubPostRepository->findBy(['club' => $club, 'parent' => null], ['created' => 'DESC']);

        $replyForms = [];
        foreach ($posts as $p) {
            $reply = new ClubPost();
            $reply->setClub($club);
            $reply->setUser($user);
            $replyForms[$p->getId()] = $this->createForm(ClubPostType::class, $reply)->createView();
        }

        return $this->render('club/forum.html.twig', [
            'club' => $club,
            'posts' => $posts,
            'form' => $form->createView(),
            'replyForms' => $replyForms,
        ]);
    }

    #[Route('/club/{club}/book/{book}/forum', name: 'club_book_forum')]
    public function clubBookForum(
        Club $club,
        Book $book,
        ClubBookPostRepository $clubBookPostRepository,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response
    {
        $user = $this->getUser();

        if (!$this->isGranted('ROLE_ADMIN') && !$club->getMembers()->contains($user)) {
            $this->addFlash('warning', 'You must be a member of this club to view the forum.');
            return $this->redirectToRoute('club_show', ['id' => $club->getClubBooks()->first()?->getBook()?->getId()]);
        }

        $post = new ClubBookPost();
        $post->setClub($club);
        $post->setBook($book);
        $post->setUser($user);

        $form = $this->createForm(ClubBookPostType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $parentId = $request->request->get('parent_id');
            if ($parentId) {
                $parent = $clubBookPostRepository->find($parentId);
                if ($parent) {
                    $post->setParent($parent);
                }
            }
            $entityManager->persist($post);
            $entityManager->flush();

            $this->addFlash('success', 'Your post has been added.');
            return $this->redirectToRoute('club_book_forum', ['club' => $club->getId(), 'book' => $book->getId()]);
        }

        $posts = $clubBookPostRepository->findBy(['club' => $club, 'book' => $book, 'parent' => null], ['created' => 'DESC']);

        $replyForms = [];
        foreach ($posts as $p) {
            $reply = new ClubBookPost();
            $reply->setClub($club);
            $reply->setUser($user);
            $replyForms[$p->getId()] = $this->createForm(ClubBookPostType::class, $reply)->createView();
        }

        return $this->render('club/book_forum.html.twig', [
            'club' => $club,
            'book' => $book,
            'posts' => $posts,
            'form' => $form->createView(),
            'replyForms' => $replyForms,
        ]);
    }
}

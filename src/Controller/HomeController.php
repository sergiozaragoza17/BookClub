<?php

namespace App\Controller;

use App\Repository\BookRepository;
use App\Repository\ClubRepository;
use App\Repository\ReviewRepository;
use App\Repository\UserBookRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\VarDumper\VarDumper;

class HomeController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function index(ReviewRepository $reviewRepository, BookRepository $bookRepository, UserBookRepository $userBookRepository, ClubRepository $clubRepository): Response
    {
        $reviews = $reviewRepository->findBy(
            ['status' => 'approved'],
            ['created' => 'DESC'],
            12
        );

        $books = $bookRepository->findBy([], ['created' => 'DESC'], 10);
        $userBooksStatus = [];
        $currentUser = $this->getUser();
        foreach ($books as $book) {
            $userBook = null;
            if ($currentUser) {
                $userBook = $userBookRepository->findOneBy(['user' => $currentUser, 'book' => $book]);
            }
            $userBooksStatus[$book->getId()] = $userBook ? true : false;
        }

        $topBooks = $bookRepository->findTopBooksByFiveStarReviews(3);

        $popularClubs = $clubRepository->getMostPopular(5);

        return $this->render('home/index.html.twig', [
            'title' => 'Welcome to BookClub 📚',
            'reviews' => $reviews,
            'books' => $books,
            'userBooksStatus' => $userBooksStatus,
            'topBooks' => $topBooks,
            'popularClubs' => $popularClubs,
        ]);
    }
}

<?php

namespace App\DataFixtures;

use App\Entity\UserBook;
use App\Entity\User;
use App\Entity\Book;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class UserBookFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create();

        $books = $manager->getRepository(Book::class)->findAll();
        $users = $manager->getRepository(User::class)->findAll();

        foreach ($users as $user) {
            $numBooks = $faker->numberBetween(1, 10);
            $userBooks = $faker->randomElements($books, $numBooks);

            foreach ($userBooks as $book) {
                $userBook = new UserBook();
                $userBook->setUser($user);
                $userBook->setBook($book);
                $statuses = ['pending', 'reading', 'finished'];
                $userBook->setStatus($faker->randomElement($statuses));
                $manager->persist($userBook);
            }
        }

        $manager->flush();
    }
}

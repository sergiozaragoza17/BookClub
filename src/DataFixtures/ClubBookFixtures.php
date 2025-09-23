<?php

namespace App\DataFixtures;

use App\Entity\Club;
use App\Entity\Book;
use App\Entity\ClubBook;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class ClubBookFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create();
        $clubs = $manager->getRepository(Club::class)->findAll();

        foreach ($clubs as $club) {
            $books = $manager->getRepository(Book::class)->findBy([
                'genre' => $club->getGenre()
            ]);

            if (empty($books)) {
                continue;
            }

            $numBooks = min(count($books), $faker->numberBetween(3, 8));
            $clubBooks = $faker->randomElements($books, $numBooks);

            foreach ($clubBooks as $book) {
                $clubBook = new ClubBook();
                $clubBook->setClub($club);
                $clubBook->setBook($book);

                $members = $club->getMembers()->toArray();
                if (!empty($members)) {
                    $addedBy = $faker->randomElement($members);
                    $clubBook->setAddedBy($addedBy);
                }

                $manager->persist($clubBook);
            }
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            BookFixtures::class,
            ClubFixtures::class,
        ];
    }
}

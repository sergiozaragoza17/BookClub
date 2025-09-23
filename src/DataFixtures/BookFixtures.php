<?php

namespace App\DataFixtures;

use App\Entity\Book;
use App\Entity\Genre;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class BookFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create();


        for ($i = 0; $i < 30; $i++) {
            $book = new Book();
            $book->setTitle($faker->sentence(3)); // 3 word title
            $book->setAuthor($faker->name()); // autor random
            $book->setPublishedYear($faker->numberBetween(1950, 2025));
            $book->setDescription($faker->paragraph(4)); // random description
            $book->setCoverImage("https://picsum.photos/200/300?random=" . $i); // placeholder image
            $book->setCreated($faker->dateTimeBetween('-2 years', 'now'));

            $randomGenreName = $faker->randomElement(['Fantasy','Science Fiction','Mystery','Romance','History','Horror','Biography']);
            /** @var Genre $genre */
            $genre = $this->getReference('genre_' . $randomGenreName);
            $book->setGenre($genre);

            $manager->persist($book);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
            GenreFixtures::class
        ];
    }
}
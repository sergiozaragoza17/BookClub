<?php

namespace App\DataFixtures;

use App\Entity\Book;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class BookFixtures extends Fixture implements FixtureGroupInterface
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

            $manager->persist($book);
        }

        $manager->flush();
    }

    public static function getGroups(): array
    {
        return ['book'];
    }
}
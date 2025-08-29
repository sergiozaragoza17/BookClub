<?php

namespace App\DataFixtures;

use App\Entity\Book;
use App\Entity\Review;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Faker\Factory;

class ReviewFixtures extends Fixture implements FixtureGroupInterface
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create();

        $books = $manager->getRepository(Book::class)->findAll();
        $allUsers = $manager->getRepository(User::class)->findAll();
        $users = array_filter($allUsers, function (User $user) {
            return in_array('ROLE_USER', $user->getRoles(), true);
        });

        if (empty($users)) {
            echo "⚠️ No hay usuarios con ROLE_USER, no se crearán reviews.\n";
            return;
        }

        foreach ($books as $book) {
            $numReviews = $faker->numberBetween(1, 5);

            for ($i = 0; $i < $numReviews; $i++) {
                $review = new Review();
                $review->setBook($book);
                $review->setUser($faker->randomElement($users));
                $review->setContent($faker->paragraph(2));
                $review->setRating($faker->numberBetween(1, 5));
                $statusOptions = ['approved', 'pending', 'rejected'];
                $review->setStatus($faker->randomElement($statusOptions));
                $review->setCreated(new \DateTimeImmutable('-' . $faker->numberBetween(1, 365) . ' days'));

                $manager->persist($review);
            }
        }

        $manager->flush();
    }

    public static function getGroups(): array
    {
        return ['review'];
    }
}
<?php

namespace App\DataFixtures;

use App\Entity\Club;
use App\Entity\ClubBook;
use App\Entity\Review;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class ClubReviewFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create();

        $clubs = $manager->getRepository(Club::class)->findAll();
        $allUsers = $manager->getRepository(User::class)->findAll();
        $users = array_filter($allUsers, fn(User $user) => in_array('ROLE_USER', $user->getRoles(), true));

        if (empty($users)) {
            echo "No users with ROLE_USER found, no club reviews will be created.\n";
            return;
        }

        foreach ($clubs as $club) {
            $clubBooks = $manager->getRepository(ClubBook::class)->findBy(['club' => $club]);
            if (empty($clubBooks)) {
                continue;
            }

            foreach ($clubBooks as $clubBook) {
                $numReviews = $faker->numberBetween(1, 3);

                for ($i = 0; $i < $numReviews; $i++) {
                    $review = new Review();
                    $review->setBook($clubBook->getBook());
                    $review->setUser($faker->randomElement($users));
                    $review->setContent($faker->paragraph(2));
                    $review->setRating($faker->numberBetween(1, 5));
                    $review->setStatus('approved');
                    $review->setCreated(new \DateTimeImmutable('-' . $faker->numberBetween(1, 365) . ' days'));

                    // Asociación a club (solo para club reviews)
                    $review->setClub($club);

                    $manager->persist($review);
                }
            }
        }

        $manager->flush();
    }
}

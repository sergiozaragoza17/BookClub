<?php

namespace App\DataFixtures;

use App\Entity\ClubPost;
use App\Entity\Club;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class ClubPostFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create();
        $clubs = $manager->getRepository(Club::class)->findAll();

        foreach ($clubs as $club) {
            $members = $club->getMembers()->toArray();
            $numPosts = $faker->numberBetween(1, 5);

            for ($i = 0; $i < $numPosts; $i++) {
                $post = new ClubPost();
                $post->setClub($club);
                $post->setUser($faker->randomElement($members));
                $post->setContent($faker->paragraph());
                $post->setCreated(new \DateTimeImmutable('-' . $faker->numberBetween(1, 60) . ' days'));

                $manager->persist($post);
            }
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
            ClubFixtures::class
        ];
    }
}
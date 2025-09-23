<?php

namespace App\DataFixtures;

use App\Entity\Club;
use App\Entity\Genre;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class ClubFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create();
        $genres = $manager->getRepository(Genre::class)->findAll();
        $users = $manager->getRepository(User::class)->findAll();

        foreach ($genres as $genre) {
            for ($i = 0; $i < 2; $i++) {
                $club = new Club();
                $club->setName($faker->company . " Club");
                $club->setDescription($faker->paragraph(2));
                $club->setGenre($genre);

                $creator = $faker->randomElement($users);
                $club->setCreatedBy($creator);

                $members = $faker->randomElements($users, $faker->numberBetween(2, 5));

                if (!in_array($creator, $members, true)) {
                    $members[] = $creator;
                }

                foreach ($members as $member) {
                    $club->addMember($member);
                }

                $manager->persist($club);
            }
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            GenreFixtures::class,
        ];
    }
}

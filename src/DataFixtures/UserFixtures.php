<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Faker\Factory;

class UserFixtures extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create();

        // Admin user
        $admin = new User();
        $admin->setEmail('admin@example.com');
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setUsername('admin');
        $admin->setName('admin');
        $admin->setJoinedAt(new \DateTimeImmutable('-' . $faker->numberBetween(1, 60) . ' days'));
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'adminpass'));
        $manager->persist($admin);

        // Regular users
        for ($i = 0; $i < 10; $i++) {
            $user = new User();
            $user->setEmail($faker->unique()->safeEmail());
            $user->setUsername($faker->unique()->userName());
            $user->setName($faker->unique()->name());
            $user->setRoles(['ROLE_USER']);
            $user->setJoinedAt(new \DateTimeImmutable('-' . $faker->numberBetween(1, 60) . ' days'));
            $user->setPassword($this->passwordHasher->hashPassword($user, 'userpass'));
            $manager->persist($user);
        }

        $manager->flush();
    }
}

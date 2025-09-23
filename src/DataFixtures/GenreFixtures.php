<?php

namespace App\DataFixtures;

use App\Entity\Genre;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class GenreFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $genres = ['Fantasy', 'Science Fiction', 'Mystery', 'Romance', 'History', 'Horror', 'Biography'];

        foreach ($genres as $name) {
            $genre = new Genre();
            $genre->setName($name);
            $manager->persist($genre);

            $this->addReference('genre_' . $name, $genre);
        }

        $manager->flush();
    }
}
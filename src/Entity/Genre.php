<?php

namespace App\Entity;

use App\Repository\ClubRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Genre
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100, unique: true)]
    private ?string $name = null;

    #[ORM\OneToMany(targetEntity: Book::class, mappedBy: 'genre')]
    private Collection $books;

    #[ORM\OneToMany(targetEntity: Club::class, mappedBy: 'genre')]
    private Collection $clubs;

    public function __construct()
    {
        $this->books = new ArrayCollection();
        $this->clubs = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getBooks(): Collection
    {
        return $this->books;
    }

    public function setBooks(Collection $books): void
    {
        $this->books = $books;
    }

    public function getClubs(): Collection
    {
        return $this->clubs;
    }

    public function setClubs(Collection $clubs): void
    {
        $this->clubs = $clubs;
    }


}

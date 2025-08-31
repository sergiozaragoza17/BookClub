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

    #[ORM\OneToMany(mappedBy: 'genre', targetEntity: Book::class)]
    private Collection $books;

    #[ORM\OneToMany(mappedBy: 'genre', targetEntity: Club::class)]
    private Collection $clubs;

    public function __construct()
    {
        $this->books = new ArrayCollection();
        $this->clubs = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }
    public function getName(): ?string { return $this->name; }
    public function setName(string $name): static { $this->name = $name; return $this; }
}

<?php

namespace App\Entity;

use App\Repository\ClubBookRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ClubBookRepository::class)]
class ClubBook
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Club::class, inversedBy: 'clubBooks')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Club $club = null;

    #[ORM\ManyToOne(targetEntity: Book::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Book $book = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $addedBy = null;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getClub(): ?Club
    {
        return $this->club;
    }

    public function setClub(Club $club): static
    {
        $this->club = $club;
        return $this;
    }

    public function getBook(): ?Book
    {
        return $this->book;
    }

    public function setBook(Book $book): static
    {
        if ($this->club && $book->getGenre() !== $this->club->getGenre()) {
            throw new \InvalidArgumentException('The book genre does not match the club genre.');
        }
        $this->book = $book;
        return $this;
    }

    public function getAddedBy(): ?User
    {
        return $this->addedBy;
    }

    public function setAddedBy(User $user): static
    {
        $this->addedBy = $user;
        return $this;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    /**
     * @param \DateTimeInterface $createdAt
     */
    public function setCreatedAt(\DateTimeInterface $createdAt): void
    {
        $this->createdAt = $createdAt;
    }


}
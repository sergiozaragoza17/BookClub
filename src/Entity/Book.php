<?php

namespace App\Entity;

use App\Repository\BookRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BookRepository::class)]
class Book
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;
    #[ORM\Column(length: 180)]
    private string $title;

    #[ORM\Column(length: 180)]
    private string $author;

    #[ORM\Column(type: 'integer')]
    private int $publishedYear;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $created = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $coverImage = null;

    #[ORM\ManyToOne(targetEntity: Genre::class, inversedBy: 'books')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Genre $genre = null;

    #[ORM\OneToMany(targetEntity: Review::class, mappedBy: 'book')]
    private Collection $reviews;

    #[ORM\OneToMany(targetEntity: UserBook::class, mappedBy: 'book')]
    private Collection $userBooks;

    #[ORM\OneToMany(targetEntity: ClubBookPost::class, mappedBy: 'book', orphanRemoval: true)]
    private Collection $bookPosts;

    public function __construct()
    {
        $this->reviews = new ArrayCollection();
        $this->userBooks = new ArrayCollection();
        $this->created = new \DateTimeImmutable();
        $this->bookPosts = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getReviews(): Collection
    {
        return $this->reviews;
    }

    /**
     * @return Collection|UserBook[]
     */
    public function getUserBooks(): Collection
    {
        return $this->userBooks;
    }

    public function addUserBook(UserBook $userBook): self
    {
        if (!$this->userBooks->contains($userBook)) {
            $this->userBooks[] = $userBook;
            $userBook->setBook($this);
        }
        return $this;
    }

    public function removeUserBook(UserBook $userBook): self
    {
        $this->userBooks->removeElement($userBook);
        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function getAuthor(): string
    {
        return $this->author;
    }

    public function setAuthor(string $author): self
    {
        $this->author = $author;
        return $this;
    }

    public function getPublishedYear(): int
    {
        return $this->publishedYear;
    }

    public function setPublishedYear(int $publishedYear): self
    {
        $this->publishedYear = $publishedYear;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getCoverImage(): ?string
    {
        return $this->coverImage;
    }

    public function setCoverImage(?string $coverImage): self
    {
        $this->coverImage = $coverImage;
        return $this;
    }

    public function getCreated(): ?\DateTimeInterface
    {
        return $this->created;
    }

    /**
     * @return Genre|null
     */
    public function getGenre(): ?Genre
    {
        return $this->genre;
    }

    /**
     * @param Genre|null $genre
     */
    public function setGenre(?Genre $genre): void
    {
        $this->genre = $genre;
    }

    public function setCreated(\DateTimeInterface $created): self
    {
        $this->created = $created;
        return $this;
    }

    public function __toString(): string
    {
        return $this->title ?? '';
    }

    public function getClubBookPosts(): Collection
    {
        return $this->bookPosts;
    }

    public function addClubBookPost(ClubBookPost $post): self
    {
        if (!$this->bookPosts->contains($post)) {
            $this->bookPosts->add($post);
            $post->setBook($this);
        }
        return $this;
    }

    public function removeClubBookPost(ClubBookPost $post): self
    {
        if ($this->bookPosts->removeElement($post)) {
            if ($post->getBook() === $this) {
                $post->setBook(null);
            }
        }
        return $this;
    }

}

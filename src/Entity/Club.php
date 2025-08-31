<?php

namespace App\Entity;

use App\Repository\ClubRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ClubRepository::class)]
class Club
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;
    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(type: 'text')]
    private ?string $description = null;

    #[ORM\ManyToOne(targetEntity: Genre::class, inversedBy: 'clubs')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Genre $genre = null;

    #[ORM\ManyToMany(targetEntity: User::class, inversedBy: 'clubs')]
    private Collection $members;

    #[ORM\OneToMany(targetEntity: ClubBook::class, mappedBy: 'club', cascade: ['persist', 'remove'])]
    private Collection $clubBooks;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $createdBy = null;

    #[ORM\OneToMany(targetEntity: Review::class, mappedBy: 'club', orphanRemoval: true)]
    private Collection $reviews;

    #[ORM\OneToMany(targetEntity: ClubPost::class, mappedBy: 'club', orphanRemoval: true)]
    private Collection $posts;

    #[ORM\OneToMany(targetEntity: ClubBookPost::class, mappedBy: 'club', orphanRemoval: true)]
    private Collection $bookPosts;

    public function __construct()
    {
        $this->members = new ArrayCollection();
        $this->clubBooks = new ArrayCollection();
        $this->reviews = new ArrayCollection();
        $this->posts = new ArrayCollection();
        $this->bookPosts = new ArrayCollection();
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getGenre(): ?Genre
    {
        return $this->genre;
    }

    public function setGenre(?Genre $genre): void
    {
        $this->genre = $genre;
    }

    /**
     * @return Collection<int, User>
     */
    public function getMembers(): Collection
    {
        return $this->members;
    }

    public function addMember(User $user): static
    {
        if (!$this->members->contains($user)) {
            $this->members->add($user);
        }
        return $this;
    }

    public function removeMember(User $user): static
    {
        $this->members->removeElement($user);
        return $this;
    }

    /**
     * @return Collection<int, ClubBook>
     */
    public function getClubBooks(): Collection
    {
        return $this->clubBooks;
    }

    public function addClubBook(ClubBook $clubBook): static
    {
        if (!$this->clubBooks->contains($clubBook)) {
            $this->clubBooks->add($clubBook);
            $clubBook->setClub($this);
        }
        return $this;
    }

    public function removeClubBook(ClubBook $clubBook): static
    {
        $this->clubBooks->removeElement($clubBook);
        return $this;
    }

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(User $user): static
    {
        $this->createdBy = $user;
        return $this;
    }

    /**
     * @return Collection<int, Review>
     */
    public function getReviews(): Collection
    {
        return $this->reviews;
    }

    public function addReview(Review $review): self
    {
        if (!$this->reviews->contains($review)) {
            $this->reviews->add($review);
            $review->setClub($this);
        }

        return $this;
    }

    public function removeReview(Review $review): self
    {
        if ($this->reviews->removeElement($review)) {
            if ($review->getClub() === $this) {
                $review->setClub(null);
            }
        }

        return $this;
    }

    public function getClubPosts(): Collection
    {
        return $this->posts;
    }

    public function addClubPost(ClubPost $post): self
    {
        if (!$this->posts->contains($post)) {
            $this->posts->add($post);
            $post->setUser($this);
        }
        return $this;
    }

    public function removeClubPost(ClubBookPost $post): self
    {
        if ($this->posts->removeElement($post)) {
            if ($post->getUser() === $this) {
                $post->setUser(null);
            }
        }
        return $this;
    }

    public function getClubBookPosts(): Collection
    {
        return $this->bookPosts;
    }

    public function addClubBookPost(ClubBookPost $post): self
    {
        if (!$this->bookPosts->contains($post)) {
            $this->bookPosts->add($post);
            $post->setUser($this);
        }
        return $this;
    }

    public function removeClubBookPost(ClubBookPost $post): self
    {
        if ($this->bookPosts->removeElement($post)) {
            if ($post->getUser() === $this) {
                $post->setUser(null);
            }
        }
        return $this;
    }

}

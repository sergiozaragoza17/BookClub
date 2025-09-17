<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[UniqueEntity(fields: ['email'], message: 'There is already an account with this email')]
#[UniqueEntity(fields: ['username'], message: 'There is already an account with this username')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    private ?string $email = null;

    #[ORM\Column]
    private array $roles = [];

    #[ORM\OneToMany(targetEntity: UserBook::class, mappedBy: 'user')]
    private Collection $userBooks;

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    #[ORM\ManyToMany(targetEntity: Club::class, mappedBy: 'members')]
    private Collection $clubs;

    #[ORM\OneToMany(targetEntity: Review::class, mappedBy: 'user')]
    private Collection $reviews;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 50, unique: true)]
    private ?string $username = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $bio = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $profileImage = null;

    #[ORM\Column(nullable: true)]
    private ?array $favGenres = null;

    #[ORM\ManyToOne(targetEntity: Book::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Book $favBook = null;

    #[ORM\ManyToOne(targetEntity: Book::class)]
    private ?Book $currentlyReading = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $joinedAt = null;

    #[ORM\OneToMany(targetEntity: ClubPost::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $posts;

    #[ORM\OneToMany(targetEntity: ClubBookPost::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $bookPosts;

    public function __construct()
    {
        $this->clubs = new ArrayCollection();
        $this->reviews = new ArrayCollection();
        $this->userBooks = new ArrayCollection();
        $this->posts = new ArrayCollection();
        $this->bookPosts = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getClubs(): Collection
    {
        return $this->clubs;
    }

    public function addClub(Club $club): static
    {
        if (!$this->clubs->contains($club)) {
            $this->clubs->add($club);
            $club->addMember($this);
        }
        return $this;
    }

    public function removeClub(Club $club): static
    {
        if ($this->clubs->removeElement($club)) {
            $club->removeMember($this);
        }
        return $this;
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
        $this->userBooks->removeElement($userBook);
        return $this;
    }

    public function removeUserBook(UserBook $userBook): self
    {
        if ($this->userBooks->removeElement($userBook)) {
            if ($userBook->getUser() === $this) {
                $userBook->setUser(null);
            }
        }
        return $this;
    }

    public function getReviews(): Collection
    {
        return $this->reviews;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
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

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): static
    {
        $this->username = $username;
        return $this;
    }

    public function getBio(): ?string
    {
        return $this->bio;
    }

    public function setBio(?string $bio): static
    {
        $this->bio = $bio;

        return $this;
    }

    public function getProfileImage(): ?string
    {
        return $this->profileImage;
    }

    public function setProfileImage(?string $profileImage): static
    {
        $this->profileImage = $profileImage;

        return $this;
    }

    public function getFavGenres(): ?array
    {
        return $this->favGenres;
    }

    public function setFavGenres(?array $favGenres): static
    {
        $this->favGenres = $favGenres;

        return $this;
    }

    public function getFavBook(): ?Book
    {
        return $this->favBook;
    }

    public function setFavBook(?Book $favBook): static
    {
        $this->favBook = $favBook;
        return $this;
    }

    public function getCurrentlyReading(): ?Book
    {
        return $this->currentlyReading;
    }

    public function setCurrentlyReading(?Book $currentlyReading): static
    {
        $this->currentlyReading = $currentlyReading;

        return $this;
    }

    public function getJoinedAt(): ?\DateTimeImmutable
    {
        return $this->joinedAt;
    }

    public function setJoinedAt(\DateTimeImmutable $joinedAt): static
    {
        $this->joinedAt = $joinedAt;

        return $this;
    }

    public function __toString(): string
    {
        return $this->name;
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

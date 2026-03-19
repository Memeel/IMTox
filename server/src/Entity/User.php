<?php

namespace App\Entity;

use App\Entity\Enums\UserRole;
use App\Entity\Enums\UserState;
use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherAwareInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
class User implements
    UserInterface,
    PasswordAuthenticatedUserInterface,
    PasswordHasherAwareInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, unique: true, nullable: false)]
    private ?string $login = null;

    #[ORM\Column(length: 255)]
    private ?string $password = null;

    #[ORM\Column(length: 255, unique: true, nullable: false)]
    private ?string $mail = null;

    #[ORM\Column(length: 255)]
    private ?string $fullname = null;

    #[ORM\Column(length: 32, enumType: UserRole::class, nullable: false)]
    private UserRole $role = UserRole::REPORTER;

    #[ORM\Column(length: 32, enumType: UserState::class, nullable: false)]
    private UserState $state = UserState::ACTIVE;

    public function getRoles(): array {
        return [$this->role->value];
    }

    public function getUserIdentifier(): string {
        return $this->login;
    }

    public function getPasswordHasherName(): ?string {
        return null;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getLogin(): ?string
    {
        return $this->login;
    }

    public function setLogin(string $login): static
    {
        $this->login = $login;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function getMail(): ?string
    {
        return $this->mail;
    }

    public function setMail(string $mail): static
    {
        $this->mail = $mail;

        return $this;
    }

    public function getFullname(): ?string
    {
        return $this->fullname;
    }

    public function setFullname(string $fullname): static
    {
        $this->fullname = $fullname;

        return $this;
    }

    public function getRole(): UserRole
    {
        return $this->role;
    }

    public function setRole(?UserRole $role): static
    {
        if($role == NULL) {
            $this->role = UserRole::REPORTER;
        } else {
            $this->role = $role;
        }
        return $this;
    }

    public function getState(): UserState
    {
        return $this->state;
    }

    public function setState(?UserState $state): static
    {
        if($state == NULL) {
            $this->state = UserState::ACTIVE;
        } else {
            $this->state = $state;
        }

        return $this;
    }
}

<?php

namespace App\Domain;

use App\Domain\Shared\ValueObject\Email;
use App\Domain\Shared\ValueObject\Role;
use App\Domain\Shared\ValueObject\UniqId;
use DateTimeImmutable;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class User implements PasswordAuthenticatedUserInterface, UserInterface{
    private ?string $passwordHash = null;

    private function __construct(
        private readonly UniqId            $id,
        private readonly Email             $email,
        private string                     $name,
        private Role                       $role,
        private readonly DateTimeImmutable $createdAt,
    ){}

    public static function create(
        Email  $email,
        string $name,
        Role   $role = Role::CUSTOMER,
    ): self{
        return new self(
            UniqId::generate(), $email, $name, $role, new DateTimeImmutable()
        );
    }

    public function email(): Email{
        return $this->email;
    }

    public function name(): string{
        return $this->name;
    }

    public function createdAt(): DateTimeImmutable{
        return $this->createdAt;
    }

    public function updateName(string $name,): void{
        $this->name = $name;
    }

    public function hasPermission(string $permission,): bool{
        return $this->role->hasPermission($permission);
    }

    public function changeRole(Role $newRole,): void{
        $this->role = $newRole;
    }

    public function setPassword(string $plainPassword, UserPasswordHasherInterface $passwordHasher,): void{
        $this->passwordHash = $passwordHasher->hashPassword($this, $plainPassword);
    }

    public function getPassword(): ?string{
        return $this->getPasswordHash();
    }

    public function getPasswordHash(): ?string{
        return $this->passwordHash;
    }

    public function getRoles(): array{
        return ['ROLE_' . strtoupper($this->role()->value)];
    }

    public function role(): Role{
        return $this->role;
    }

    public function eraseCredentials(): void{}

    public function getUserIdentifier(): string{
        return $this->id()
                    ->toString();
    }

    public function id(): UniqId{
        return $this->id;
    }
}

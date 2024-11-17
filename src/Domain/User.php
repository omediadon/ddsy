<?php

namespace App\Domain;

use App\Domain\Shared\ValueObject\Email;
use App\Domain\Shared\ValueObject\UniqId;
use DateTimeImmutable;

class User{
    private function __construct(
        private readonly UniqId            $id,
        private readonly Email             $email,
        private string                     $name,
        private readonly DateTimeImmutable $createdAt,
    ){}

    public static function create(
        Email  $email,
        string $name,
    ): self{
        return new self(
            UniqId::generate(), $email, $name, new DateTimeImmutable()
        );
    }

    public function id(): UniqId{
        return $this->id;
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
}

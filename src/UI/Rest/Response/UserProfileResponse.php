<?php

namespace App\UI\Rest\Response;

use App\Domain\User;
use DateTimeImmutable;
use JsonSerializable;

final readonly class UserProfileResponse implements JsonSerializable{
    public function __construct(
        private string            $id,
        private string            $email,
        private string            $name,
        private DateTimeImmutable $createdAt,
    ){}

    public static function fromUserEntity(User $user,): self{
        return new self(id: $user->id()
                                 ->toString(), email: $user->email()
                                                           ->toString(), name: $user->name(), createdAt: $user->createdAt());
    }

    public function jsonSerialize(): array{
        return [
            'id'         => $this->id,
            'email'      => $this->email,
            'name'       => $this->name,
            'created_at' => $this->createdAt->format('c')
        ];
    }
}

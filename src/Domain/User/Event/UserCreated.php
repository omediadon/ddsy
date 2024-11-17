<?php

namespace App\Domain\User\Event;

use App\Domain\Shared\Event\DomainEventInterface;
use App\Domain\Shared\ValueObject\UniqId;

final readonly class UserCreated implements DomainEventInterface{
    public function __construct(
        private UniqId $userId,
    ){}

    public function userId(): UniqId{
        return $this->userId;
    }
}

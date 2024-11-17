<?php

namespace App\Domain\User\Specification;

use App\Domain\Shared\ValueObject\Email;
use App\Domain\User\Repository\UserRepositoryInterface;

final readonly class UniqueEmailSpecification{
    public function __construct(
        private UserRepositoryInterface $userRepository,
    ){}

    public function isSatisfiedBy(Email $email,): bool{
        return null === $this->userRepository->findByEmail($email);
    }
}

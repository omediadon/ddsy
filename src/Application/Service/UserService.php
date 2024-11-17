<?php

namespace App\Application\Service;

use App\Domain\Shared\ValueObject\Email;
use App\Domain\User\Exception\EmailAlreadyExistsException;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\User\Specification\UniqueEmailSpecification;

final readonly class UserService{
    public function __construct(
        private UserRepositoryInterface  $userRepository,
        private UniqueEmailSpecification $uniqueEmailSpec,
    ){}

    public function ensureEmailIsUnique(Email $email,): void{
        if(!$this->uniqueEmailSpec->isSatisfiedBy($email)){
            throw new EmailAlreadyExistsException($email->toString());
        }
    }
}

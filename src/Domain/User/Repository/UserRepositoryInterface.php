<?php

namespace App\Domain\User\Repository;

use App\Domain\Shared\ValueObject\Email;
use App\Domain\Shared\ValueObject\UniqId;
use App\Domain\User;

interface UserRepositoryInterface{
    public function save(User $user,): void;

    public function findById(UniqId $id,): ?User;

    public function findByEmail(Email $email,): ?User;
}
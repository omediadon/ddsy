<?php

namespace App\Application\Command\CreateUser;

final readonly class CreateUserCommand{
    public function __construct(
        public string $email,
        public string $name,
        public string $password,
    ){}
}

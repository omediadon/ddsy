<?php

namespace App\UI\Rest\Request;

use App\Application\Command\CreateUser\CreateUserCommand;
use App\Infrastructure\Validator as AppAssert;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;

class RegisterRequest{

    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Email]
        #[AppAssert\UniqueEmail]
        public string $email,

        #[Assert\NotBlank]
        #[Assert\Length(min: 3, max: 50)]
        public string $name,

        #[Assert\NotBlank]
        #[Assert\Length(min: 8)]
        public string $password,
    ){}

    public static function fromRequest(Request $request,): self{
        $data     = $request->toArray();
        $email    = $data['email'];
        $name     = $data['name'];
        $password = $data['password'];

        return new self(email: $email, name: $name, password: $password);
    }

    public function toCreateUserCommand(): CreateUserCommand{
        return new CreateUserCommand($this->email, $this->name, $this->password);
    }
}

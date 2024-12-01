<?php

namespace App\UI\Rest\Request;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;

class LoginRequest{

    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Email]
        public string $email,

        #[Assert\NotBlank]
        public string $password,
    ){}

    public static function fromRequest(Request $request,): self{
        $data     = $request->toArray();
        $email    = $data['email'];
        $password = $data['password'];

        return new self(email: $email, password: $password);
    }

}

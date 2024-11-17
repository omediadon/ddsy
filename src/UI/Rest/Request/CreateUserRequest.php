<?php

namespace App\UI\Rest\Request;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class CreateUserRequest{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Email]
        public string $email,

        #[Assert\NotBlank]
        #[Assert\Length(min: 2, max: 50)]
        public string $name,
    ){}

    public static function fromRequest(Request $request,): self{
        $data = json_decode($request->getContent(), true);

        return new self(
            email: $data['email'] ?? '', name: $data['name'] ?? ''
        );
    }
}

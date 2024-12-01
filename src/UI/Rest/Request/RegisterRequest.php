<?php

namespace App\UI\Rest\Request;

use App\Infrastructure\Validator as AppAssert;
use Symfony\Component\Validator\Constraints as Assert;

class RegisterRequest{
    #[Assert\NotBlank]
    #[Assert\Email]
    #[AppAssert\UniqueEmail]
    public string $email;

    #[Assert\NotBlank]
    #[Assert\Length(min: 3, max: 50)]
    public string $name;

    #[Assert\NotBlank]
    #[Assert\Length(min: 8)]
    public string $password;
}

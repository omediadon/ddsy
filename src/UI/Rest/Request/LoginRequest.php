<?php

namespace App\UI\Rest\Request;

use Symfony\Component\Validator\Constraints as Assert;

class LoginRequest{
    #[Assert\NotBlank]
    #[Assert\Email]
    public string $email;

    #[Assert\NotBlank]
    public string $password;
}

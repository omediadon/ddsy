<?php

namespace App\Infrastructure\Validator;

use Attribute;
use Symfony\Component\Validator\Constraint;

#[Attribute]
class UniqueEmail extends Constraint{
    public string $message = 'This email is already in use.';

    public function validatedBy(): string{
        return UniqueEmailValidator::class;
    }
}
<?php

namespace App\Domain\Shared\ValueObject;

use InvalidArgumentException;

final readonly class Email{
    private function __construct(private string $value,){
        if(!filter_var($value, FILTER_VALIDATE_EMAIL)){
            throw new InvalidArgumentException('Invalid email format');
        }
    }

    public static function fromString(string $value,): self{
        return new self($value);
    }

    public function toString(): string{
        return $this->value;
    }

    public function __toString(): string{
        return $this->value;
    }
}
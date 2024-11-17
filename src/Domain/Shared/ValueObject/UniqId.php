<?php

namespace App\Domain\Shared\ValueObject;

use InvalidArgumentException;

final readonly class UniqId{
    private function __construct(private string $value,){
        if(empty($value)){
            throw new InvalidArgumentException('User ID cannot be empty');
        }
    }

    public static function generate(): self{
        return new self(uuid_create());
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

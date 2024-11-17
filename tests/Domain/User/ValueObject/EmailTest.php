<?php

namespace App\Tests\Domain\User\ValueObject;

use App\Domain\Shared\ValueObject\Email;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class EmailTest extends TestCase{
    public function testValidEmail(): void{
        $email = Email::fromString('test@example.com');
        $this->assertEquals('test@example.com', $email->toString());
    }

    public function testInvalidEmail(): void{
        $this->expectException(InvalidArgumentException::class);
        Email::fromString('invalid-email');
    }
}

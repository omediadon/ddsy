<?php

namespace App\Tests\Domain\User;

use App\Domain\Shared\ValueObject\Email;
use App\Domain\Shared\ValueObject\UniqId;
use App\Domain\User;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase{
    public function testCreateUser(): void{
        $email = Email::fromString('test@example.com');
        $name  = 'John Doe';

        $user = User::create($email, $name);

        $this->assertInstanceOf(User::class, $user);
        $this->assertInstanceOf(UniqId::class, $user->id());
        $this->assertEquals($email, $user->email());
        $this->assertEquals($name, $user->name());
        $this->assertInstanceOf(DateTimeImmutable::class, $user->createdAt());
    }

    public function testUpdateName(): void{
        $user = User::create(
            Email::fromString('test@example.com'),
            'John Doe'
        );

        $newName = 'Jane Doe';
        $user->updateName($newName);

        $this->assertEquals($newName, $user->name());
    }
}

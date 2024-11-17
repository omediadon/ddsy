<?php

namespace App\Tests\UI\Rest\Request;

use App\UI\Rest\Request\CreateUserRequest;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Mapping\Loader\AttributeLoader;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class CreateUserRequestTest extends TestCase{
    private ValidatorInterface $validator;

    public function testCreateFromValidRequest(): void{
        // Arrange
        $content = json_encode([
                                   'email' => 'test@example.com',
                                   'name'  => 'Test User'
                               ]);
        $request = new Request([], [], [], [], [], [], $content);

        // Act
        $createUserRequest = CreateUserRequest::fromRequest($request);

        // Assert
        $this->assertEquals('test@example.com', $createUserRequest->email);
        $this->assertEquals('Test User', $createUserRequest->name);

        $violations = $this->validator->validate($createUserRequest);
        $this->assertCount(0, $violations);
    }

    public function testValidationFailsWithInvalidEmail(): void{
        // Arrange
        $content = json_encode([
                                   'email' => 'not-an-email',
                                   'name'  => 'Test User'
                               ]);
        $request = new Request([], [], [], [], [], [], $content);

        // Act
        $createUserRequest = CreateUserRequest::fromRequest($request);
        $violations        = $this->validator->validate($createUserRequest);

        // Assert
        $this->assertCount(1, $violations);
        $this->assertEquals('email', $violations[0]->getPropertyPath());
    }

    public function testValidationFailsWithEmptyName(): void{
        // Arrange
        $content = json_encode([
                                   'email' => 'test@example.com',
                                   'name'  => ''
                               ]);
        $request = new Request([], [], [], [], [], [], $content);

        // Act
        $createUserRequest = CreateUserRequest::fromRequest($request);
        $violations        = $this->validator->validate($createUserRequest);

        // Assert
        $this->assertCount(2, $violations);
        $this->assertEquals('name', $violations[0]->getPropertyPath());
        $this->assertEquals('name', $violations[1]->getPropertyPath());
    }

    public function testValidationFailsWithTooLongName(): void{
        // Arrange
        $content = json_encode([
                                   'email' => 'test@example.com',
                                   'name'  => str_repeat('a', 51)
                                   // 51 characters
                               ]);
        $request = new Request([], [], [], [], [], [], $content);

        // Act
        $createUserRequest = CreateUserRequest::fromRequest($request);
        $violations        = $this->validator->validate($createUserRequest);

        // Assert
        $this->assertCount(1, $violations);
        $this->assertEquals('name', $violations[0]->getPropertyPath());
    }

    protected function setUp(): void{
        $this->validator = Validation::createValidatorBuilder()
                                     ->addLoader(new AttributeLoader()) // Enable attribute support
                                     ->getValidator();
    }
}
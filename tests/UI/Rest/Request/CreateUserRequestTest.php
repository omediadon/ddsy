<?php

namespace App\Tests\UI\Rest\Request;

use App\Domain\Shared\ValueObject\Email;
use App\Domain\User;
use App\Infrastructure\Doctrine\Repository\UserRepository;
use App\UI\Rest\Request\CreateUserRequest;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Mapping\Loader\AttributeLoader;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class CreateUserRequestTest extends KernelTestCase{
    private ValidatorInterface $validator;
    private UserRepository     $userRepository;

    public function testCreateFromValidRequest(): void{
        // Arrange
        $content = json_encode([
                                   'email' => 'test3@example.com',
                                   'name'  => 'Test User'
                               ]);
        $request = new Request([], [], [], [], [], [], $content);

        // Act
        $createUserRequest = CreateUserRequest::fromRequest($request);

        // Assert
        $this->assertEquals('test3@example.com', $createUserRequest->email);
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
                                   'email' => 'test3@example.com',
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
                                   'email' => 'test3@example.com',
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

    public function testValidationFailsWithDuplicateEmail(): void{
        //Prepare
        /**
         * @var UserRepository $repo
         */
        $repo = self::getContainer()
                    ->get(UserRepository::class);
        $repo->clear();
        $repo->save(User::create(Email::fromString('test@example.com'), 'Some Name'));

        // Arrange
        $content = json_encode([
                                   'email' => 'test@example.com',
                                   'name'  => str_repeat('a', 3)
                               ]);
        $request = new Request([], [], [], [], [], [], $content);

        // Act
        $createUserRequest = CreateUserRequest::fromRequest($request);
        $violations        = $this->validator->validate($createUserRequest);

        // Assert
        $this->assertCount(1, $violations);
        $this->assertEquals('email', $violations[0]->getPropertyPath());
    }

    protected function setUp(): void{
        global $kernel;
        $kernel = static::bootKernel();

        $kernel->getContainer();
        /* $validatorFactory = new class  implements ConstraintValidatorFactoryInterface
         {
             private array $validators = [];

             public function __construct(private readonly UserRepository $userRepository) {}

             public function getInstance(Constraint $constraint): ConstraintValidatorInterface
             {
                 $className = get_class($constraint);

                 if (!isset($this->validators[$className])) {
                     if ($className === 'App\Infrastructure\Validator\UniqueEmail') {
                         $this->validators[$className] = new UniqueEmailValidator($this->userRepository);
                     } else {
                         // Let Symfony handle other validators
                         return Validation::createValidatorBuilder()->getValidator()->getConstraintValidatorFactory()->getInstance($constraint);
                     }
                 }

                 return $this->validators[$className];
             }
         };*/
        $this->validator = Validation::createValidatorBuilder()
                                     ->addLoader(new AttributeLoader()) // Enable attribute support
            //                                     ->setConstraintValidatorFactory($validatorFactory)
                                     ->getValidator();
        self::getContainer();
    }
}
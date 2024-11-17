<?php

namespace App\Tests\Infrastructure;

use App\Domain\Shared\ValueObject\Email;
use App\Domain\User;
use App\Domain\User\Repository\UserRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class UserRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private UserRepositoryInterface $userRepository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = static::getContainer()->get('doctrine')->getManager();
        $this->userRepository = static::getContainer()->get(UserRepositoryInterface::class);

        // Begin transaction
        $this->entityManager->beginTransaction();
    }

    protected function tearDown(): void
    {
        // Rollback transaction
        if ($this->entityManager->getConnection()->isTransactionActive()) {
            $this->entityManager->rollback();
        }

        $this->entityManager->close();
        parent::tearDown();
    }

    public function testSaveAndFindUser(): void
    {
        // Create a new user
        $email = Email::fromString('test969@example.com');
        $user = User::create($email, 'John Doe');

        // Save the user
        $this->userRepository->save($user);
        $this->entityManager->flush();

        // Clear entity manager to ensure we're fetching from database
        $this->entityManager->clear();

        // Find the user by ID
        $foundUser = $this->userRepository->findById($user->id());

        $this->assertNotNull($foundUser);
        $this->assertEquals($user->id(), $foundUser->id());
        $this->assertEquals($user->email(), $foundUser->email());
        $this->assertEquals($user->name(), $foundUser->name());
    }

    public function testFindByEmail(): void
    {
        // Create and save a user
        $email = Email::fromString('test696@example.com');
        $user = User::create($email, 'John Doe');
        $this->userRepository->save($user);
        $this->entityManager->flush();

        // Clear entity manager to ensure we're fetching from database
        $this->entityManager->clear();

        // Find the user by email
        $foundUser = $this->userRepository->findByEmail($email);

        $this->assertNotNull($foundUser);
        $this->assertEquals($user->email(), $foundUser->email());
    }
}
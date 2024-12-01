<?php

namespace App\Tests\UI\Rest\Controller;

use App\Application\Command\CreateUser\CreateUserCommand;
use App\Application\Query\GetUserProfile\GetUserProfileQuery;
use App\Application\Query\ListUsers\ListUsersQuery;
use App\Domain\Shared\ValueObject\Email;
use App\Domain\Shared\ValueObject\Role;
use App\Domain\User;
use App\Infrastructure\Doctrine\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use function json_decode;
use function json_encode;

class UserControllerTest extends WebTestCase{
    /**
     * @var \Symfony\Bundle\FrameworkBundle\KernelBrowser
     */
    private static KernelBrowser       $client;
    private static MessageBusInterface $commandBus;
    private static MessageBusInterface $queryBus;
    private static UserRepository      $userRepository;
    private static User                $user;

    public function setUp(): void{
        self::$client = static::createClient();
        $container    = self::$client->getContainer();

        // Reference message buses
        self::$commandBus     = $container->get('command.bus');
        self::$queryBus       = $container->get('query.bus');
        self::$userRepository = $container->get(UserRepository::class);

        self::$userRepository->clear();
        self::$user = self::createDummyUsers();

        parent::setUp();
    }

    private static function createDummyUsers(): User{
        $user = User::create(Email::fromString('mail@something.com'), 'Some Cool Name', Role::ADMIN);
        self::$userRepository->save($user);
        self::$userRepository->save(User::create(Email::fromString('mail2@something.com'), 'Some Name'));
        self::$userRepository->save(User::create(Email::fromString('mail3@something.com'), 'Cool Name'));

        return $user;
    }

    public function tear(): void{
        self::$userRepository->clear();
    }

    public function testCreateUserSuccess(): void{
        // Arrange
        $userData = [
            'email' => 'test@example.com',
            'name'  => 'Test User'
        ];

        // Act
        self::$client->request(
            'POST',
            '/api/users',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($userData)
        );
        $response           = json_decode(
            self::$client->getResponse()
                         ->getContent(),
            true
        );
        $dispatchedMessages = self::$commandBus->getDispatchedMessages();

        // Assert
        $this->assertCount(1, $dispatchedMessages);
        $this->assertInstanceOf(CreateUserCommand::class, $dispatchedMessages[0]['message']);
        $this->assertInstanceOf(User::class, $dispatchedMessages[0]["stamps_after_dispatch"][1]->getResult());
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $this->assertArrayHasKey('id', $response);
    }

    public function testCreateUserValidationFailure(): void{
        // Arrange
        $invalidData = [
            'email' => 'not-an-email',
            'name'  => ''
        ];

        // Act
        self::$client->request(
            'POST',
            '/api/users',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($invalidData)
        );
        $dispatchedMessages = self::$commandBus->getDispatchedMessages();
        $response           = json_decode(
            self::$client->getResponse()
                         ->getContent(),
            true
        );

        // Assert
        $this->assertCount(0, $dispatchedMessages);
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $this->assertArrayHasKey('errors', $response);
    }

    public function testGetUserProfileSuccess(): void{
        // Arrange
        $userId = self::$user->id();
        self::$client->loginUser(self::$user);

        // Act
        self::$client->request('GET', "/api/users/{$userId}");

        // Prepare results
        $response           = json_decode(
            self::$client->getResponse()
                         ->getContent(),
            true
        );
        $dispatchedMessages = self::$queryBus->getDispatchedMessages();

        // Assert
        $this->assertCount(1, $dispatchedMessages);
        $this->assertInstanceOf(GetUserProfileQuery::class, $dispatchedMessages[0]['message']);
        $this->assertResponseIsSuccessful();
        $this->assertArrayHasKey('id', $response);
        $this->assertEquals($userId, $response['id']);
        $this->assertArrayHasKey('email', $response);
        $this->assertArrayHasKey('name', $response);
        $this->assertArrayHasKey('created_at', $response);
    }

    public function testGetUserProfileNotFound(): void{
        // Arrange
        $userId = 'non-existent-id';

        // Act
        self::$client->request('GET', "/api/users/{$userId}");
        $dispatchedMessages = self::$queryBus->getDispatchedMessages();

        // Assert
        $this->assertCount(1, $dispatchedMessages);
        $this->assertInstanceOf(GetUserProfileQuery::class, $dispatchedMessages[0]['message']);
        $this->assertArrayHasKey('exception', $dispatchedMessages[0]);
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testListUsersSuccess(): void{
        // Arrange
        $queryParams = [
            'page'     => '1',
            'per_page' => '15',
            'search'   => 'cool',
            'sort_by'  => 'name',
            'sort_dir' => 'DESC'
        ];

        // Act
        self::$client->request('GET', '/api/users', $queryParams);

        // Prepare results
        $response           = json_decode(
            self::$client->getResponse()
                         ->getContent(),
            true
        );
        $dispatchedMessages = self::$queryBus->getDispatchedMessages();

        // Assert
        $this->assertCount(1, $dispatchedMessages);
        $this->assertInstanceOf(ListUsersQuery::class, $dispatchedMessages[0]['message']);
        $this->assertResponseIsSuccessful();
        $this->assertArrayHasKey('items', $response);
        $this->assertCount(2, $response['items']);
        $this->assertArrayHasKey('metadata', $response);
        $this->assertEquals('1', $response['metadata']['page']);
        $this->assertEquals('15', $response['metadata']['per_page']);
    }

    public function testListUsersValidationFailure(): void{
        // Arrange
        $params = [
            'page'     => '-1',
            'per_page' => '1000',
            'sort_by'  => 'invalid_field'
        ];

        // Act
        self::$client->request('GET', '/api/users', $params);

        // Prepare results
        $response           = json_decode(
            self::$client->getResponse()
                         ->getContent(),
            true
        );
        $dispatchedMessages = self::$queryBus->getDispatchedMessages();

        // Assert
        $this->assertCount(0, $dispatchedMessages);
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $this->assertArrayHasKey('errors', $response);
    }

    public function testListUsersDefaultPagination(): void{
        // Arrange

        // Act
        self::$client->request('GET', '/api/users');

        // Prepare results
        $response           = json_decode(
            self::$client->getResponse()
                         ->getContent(),
            true
        );
        $dispatchedMessages = self::$queryBus->getDispatchedMessages();

        // Assert
        $this->assertCount(1, $dispatchedMessages);
        $this->assertInstanceOf(ListUsersQuery::class, $dispatchedMessages[0]['message']);
        $this->assertResponseIsSuccessful();
        $this->assertCount(3, $response['items']);
        $this->assertArrayHasKey('metadata', $response);
        $this->assertEquals(1, $response['metadata']['page']);
        $this->assertEquals(10, $response['metadata']['per_page']);
    }
}
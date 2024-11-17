<?php

namespace App\Tests\UI\Rest\Controller;

use App\Application\Command\CreateUser\CreateUserCommand;
use App\Application\Query\GetUserProfile\GetUserProfileQuery;
use App\Application\Query\ListUsers\ListUsersQuery;
use App\Domain\Shared\ValueObject\Email;
use App\Domain\User;
use App\Domain\User\Event\UserCreated;
use App\Domain\User\Exception\UserNotFoundException;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Infrastructure\Doctrine\Repository\DoctrineUserRepository;
use App\Infrastructure\Doctrine\Type\UniqIdType;
use App\UI\Rest\Response\UserListResponse;
use App\UI\Rest\Response\UserProfileResponse;
use DateTimeImmutable;
use stdClass;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

class UserControllerTest extends WebTestCase{
    private KernelBrowser       $client;
    private MessageBusInterface $commandBus;
    private MessageBusInterface $queryBus;

    public function testCreateUserSuccess(): void{
        // Arrange
        $userData = [
            'email' => 'test@example.com',
            'name'  => 'Test User'
        ];

        $userId = '123e4567-e89b-12d3-a456-426614174000';

        $this->commandBus->expects($this->once())
                         ->method('dispatch')
                         ->with(
                             $this->callback(function(CreateUserCommand $command,) use ($userData){
                                 return $command->email === $userData['email'] && $command->name === $userData['name'];
                             })
                         )
                         ->willReturn(new Envelope(new stdClass()));  // Return value doesn't matter

        // Act
        $this->client->request(
            'POST',
            '/api/users',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($userData)
        );

        // Assert
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $response = json_decode(
            $this->client->getResponse()
                         ->getContent(),
            true
        );
        $this->assertArrayHasKey('id', $response);
    }

    public function testCreateUserValidationFailure(): void{
        // Arrange
        $invalidData = [
            'email' => 'not-an-email',
            'name'  => ''
        ];

        // Act
        $this->client->request(
            'POST',
            '/api/users',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($invalidData)
        );

        // Assert
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $response = json_decode(
            $this->client->getResponse()
                         ->getContent(),
            true
        );
        $this->assertArrayHasKey('errors', $response);
    }

    public function testGetUserProfileSuccess(): void{
        /**
         * @var DoctrineUserRepository $repo
         */
        $repo = self::getContainer()->get(UserRepositoryInterface::class);
        $user = User::create( Email::fromString('mail@something.com'), 'Some Cool Name');
        $userId          = $user->id();
        $repo->save($user);
        // Arrange
        $profileResponse = new UserProfileResponse(
            $userId, 'mail@something.com', 'Some Cool Name', new DateTimeImmutable()
        );

        $this->queryBus->expects($this->once())
                       ->method('dispatch')
                       ->with(
                           $this->callback(function(GetUserProfileQuery $query,) use ($userId){
                               return $query->userId === $userId;
                           })
                       )
            ->willReturnCallback(function ($event) {
                $this->assertInstanceOf(UserProfileResponse::class, $event);
                return new Envelope($event);
            });
//                       ->willReturn($profileResponse);

        // Act
        $this->client->request('GET', "/api/users/{$userId}");

        // Assert
        $this->assertResponseIsSuccessful();
        $response = json_decode(
            $this->client->getResponse()
                         ->getContent(),
            true
        );
        $this->assertArrayHasKey('id', $response);
        $this->assertArrayHasKey('email', $response);
        $this->assertArrayHasKey('name', $response);
        $this->assertArrayHasKey('created_at', $response);
    }

    public function testGetUserProfileNotFound(): void{
        // Arrange
        $userId = 'non-existent-id';

        $this->queryBus->expects($this->once())
                       ->method('dispatch')
                       ->willThrowException(new UserNotFoundException());

        // Act
        $this->client->request('GET', "/api/users/{$userId}/details");

        // Assert
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testListUsersSuccess(): void{
        // Arrange
        $queryParams = [
            'page'     => '2',
            'per_page' => '15',
            'search'   => 'john',
            'sort_by'  => 'name',
            'sort_dir' => 'DESC'
        ];

        $listResponse = new UserListResponse(
            [],  // Empty users array for simplicity
            100, // total
            2,   // page
            15 // perPage
        );

        $this->queryBus->expects($this->once())
                       ->method('dispatch')
                       ->with(
                           $this->callback(function(ListUsersQuery $query,){
                               return $query->page === 2 &&
                                      $query->perPage === 15 &&
                                      $query->searchTerm === 'john' &&
                                      $query->sortBy === 'name' &&
                                      $query->sortDirection === 'DESC';
                           })
                       )
                       ->willReturn($listResponse);

        // Act
        $this->client->request('GET', '/api/users', $queryParams);

        // Assert
        $this->assertResponseIsSuccessful();
        $response = json_decode(
            $this->client->getResponse()
                         ->getContent(),
            true
        );
        $this->assertArrayHasKey('items', $response);
        $this->assertArrayHasKey('metadata', $response);
        $this->assertEquals('2', $response['metadata']['page']);
        $this->assertEquals('15', $response['metadata']['per_page']);
    }

    public function testListUsersValidationFailure(): void{
        // Act
        $this->client->request('GET', '/api/users', [
            'page'     => '-1',
            'per_page' => '1000',
            'sort_by'  => 'invalid_field'
        ]);

        // Assert
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $response = json_decode(
            $this->client->getResponse()
                         ->getContent(),
            true
        );
        $this->assertArrayHasKey('errors', $response);
    }

    public function testListUsersDefaultPagination(): void{
        // Arrange
        $listResponse = new UserListResponse(
            [], // Empty users array
            10, // total
            1,  // default page
            10  // default per_page
        );

        $this->queryBus->expects($this->once())
                       ->method('dispatch')
                       ->with(
                           $this->callback(function(ListUsersQuery $query,){
                               return $query->page === 1 &&
                                      $query->perPage === 10 &&
                                      $query->searchTerm === null &&
                                      $query->sortBy === null &&
                                      $query->sortDirection === 'ASC';
                           })
                       )
                       ->willReturn($listResponse);

        // Act
        $this->client->request('GET', '/api/users');

        // Assert
        $this->assertResponseIsSuccessful();
        $response = json_decode(
            $this->client->getResponse()
                         ->getContent(),
            true
        );
        $this->assertArrayHasKey('metadata', $response);
        $this->assertEquals(1, $response['metadata']['page']);
        $this->assertEquals(10, $response['metadata']['per_page']);
    }

    protected function setUp(): void{
        $this->client = static::createClient();

        // Mock message buses
        $this->commandBus = $this->createMock(MessageBusInterface::class);
        $this->queryBus   = $this->createMock(MessageBusInterface::class);

        // Replace service with mocks
        $this->client->getContainer()
                     ->set('command.bus', $this->commandBus);
        $this->client->getContainer()
                     ->set('query.bus', $this->queryBus);
    }
}
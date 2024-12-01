<?php

namespace App\Tests\Application\Command;

use App\Application\Command\CreateUser\CreateUserCommand;
use App\Application\Command\CreateUser\CreateUserHandler;
use App\Domain\User\Event\UserCreated;
use App\Domain\User\Repository\UserRepositoryInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

class CreateUserHandlerTest extends TestCase{
    private UserRepositoryInterface $userRepository;
    private MessageBusInterface     $eventBus;
    private CreateUserHandler       $handler;

    /**
     * @throws \Symfony\Component\Messenger\Exception\ExceptionInterface
     */
    public function testCreateUser(): void{
        $command = new CreateUserCommand(
            'test@example.com', 'John Doe'
        );

        $this->userRepository->expects($this->once())
                             ->method('save');

        $this->eventBus->expects($this->once())
                       ->method('dispatch')
                       ->willReturnCallback(function($event,){
                           $this->assertInstanceOf(UserCreated::class, $event);

                           return new Envelope($event);
                       });

        $user = ($this->handler)($command);

        $this->assertEquals(
            $command->email,
            $user->email()
                 ->toString()
        );
        $this->assertEquals($command->name, $user->name());
    }

    protected function setUp(): void{
        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->eventBus       = $this->createMock(MessageBusInterface::class);
        $this->handler        = new CreateUserHandler(
            $this->userRepository, $this->eventBus
        );
    }
}

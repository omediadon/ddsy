<?php

namespace App\Application\Command\CreateUser;

use App\Domain\Shared\ValueObject\Email;
use App\Domain\User;
use App\Domain\User\Event\UserCreated;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Infrastructure\Messenger\MessageHandler\CommandHandlerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

final readonly class CreateUserHandler implements CommandHandlerInterface{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private MessageBusInterface     $eventBus,
    ){}

    /**
     * @throws \Symfony\Component\Messenger\Exception\ExceptionInterface
     */
    #[AsMessageHandler]
    public function __invoke(CreateUserCommand $command,): User{
        $user = User::create(
            Email::fromString($command->email),
            $command->name
        );

        $this->userRepository->save($user);

        // Dispatch domain event
        $this->eventBus->dispatch(new UserCreated($user->id()));

        return $user;
    }
}

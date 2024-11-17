<?php

namespace App\Application\Query\GetUserProfile;

use App\Domain\Shared\ValueObject\UniqId;
use App\Domain\User\Exception\UserNotFoundException;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\UI\Rest\Response\UserProfileResponse;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;

final  class GetUserProfileHandler {
    use HandleTrait;

    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        MessageBusInterface                      $messageBus,
    ){
        $this->messageBus = $messageBus;
    }

    #[AsMessageHandler]
    public function __invoke(GetUserProfileQuery $query,): UserProfileResponse{
        $user = $this->userRepository->findById(
            UniqId::fromString($query->userId)
        );

        if(null === $user){
            throw new UserNotFoundException();
        }

        return  UserProfileResponse::fromUserEntity($user);
    }
}

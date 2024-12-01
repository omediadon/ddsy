<?php

namespace App\Application\Query\GetUserProfile;

use App\Domain\Shared\ValueObject\UniqId;
use App\Domain\User\Exception\AccessDeniedException;
use App\Domain\User\Exception\UserNotFoundException;
use App\Domain\User\Repository\UserRepositoryInterface;
use App\Domain\User\Services\UserAuthorizationService;
use App\Infrastructure\Messenger\MessageHandler\QueryHandlerInterface;
use App\UI\Rest\Response\UserProfileResponse;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\HandleTrait;

final  class GetUserProfileHandler implements QueryHandlerInterface{
    use HandleTrait;

    public function __construct(
        private readonly UserRepositoryInterface  $userRepository,
        private readonly UserAuthorizationService $authorizationService,
    ){}

    #[AsMessageHandler]
    public function __invoke(GetUserProfileQuery $query,): UserProfileResponse{
        // Find the current user
        $currentUser = $this->userRepository->findById(
            UniqId::fromString($query->loggedInUserId)
        );

        // Find the target user
        $targetUser = $this->userRepository->findById(
            UniqId::fromString($query->userId)
        );

        if(!$currentUser || !$targetUser){
            throw new UserNotFoundException('User not found');
        }

        // Check authorization
        if(!$this->authorizationService->canViewProfile($currentUser, $targetUser)){
            throw new AccessDeniedException('You are not authorized to view this profile');
        }

        return UserProfileResponse::fromUserEntity($targetUser);
    }
}

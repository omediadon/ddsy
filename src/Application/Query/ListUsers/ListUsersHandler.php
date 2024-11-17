<?php

namespace App\Application\Query\ListUsers;

use App\Domain\User\Repository\UserRepositoryInterface;
use App\UI\Rest\Response\UserListResponse;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

final readonly class ListUsersHandler{
    public function __construct(
        private UserRepositoryInterface $userRepository,
    ){}

    #[AsMessageHandler]
    public function __invoke(ListUsersQuery $query,): UserListResponse{
        $criteria = [];
        if($query->searchTerm){
            $criteria['search'] = $query->searchTerm;
        }

        $users = $this->userRepository->findByCriteria(
            criteria     : $criteria,
            page         : $query->page,
            perPage      : $query->perPage,
            sortBy       : $query->sortBy,
            sortDirection: $query->sortDirection
        );

        $total = $this->userRepository->countByCriteria($criteria);

        return new UserListResponse(
            users: $users, total: $total, page: $query->page, perPage: $query->perPage
        );
    }
}

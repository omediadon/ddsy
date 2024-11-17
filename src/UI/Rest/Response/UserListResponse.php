<?php

namespace App\UI\Rest\Response;

use App\Domain\User;
use JsonSerializable;

final readonly class UserListResponse implements JsonSerializable{
    /** @var UserProfileResponse[] */
    private array $items;

    /**
     * @param User[] $users
     */
    public function __construct(
        array       $users,
        private int $total,
        private int $page,
        private int $perPage,
    ){
        $this->items = array_map(
            fn(User $user,) => UserProfileResponse::fromUserEntity($user),
            $users
        );
    }

    public function jsonSerialize(): array{
        return [
            'items'    => $this->items,
            'metadata' => [
                'total'       => $this->total,
                'page'        => $this->page,
                'per_page'    => $this->perPage,
                'total_pages' => (int) ceil($this->total / $this->perPage),
                'has_more'    => $this->page * $this->perPage < $this->total
            ]
        ];
    }
}

<?php

namespace App\Application\Query\ListUsers;

final readonly class ListUsersQuery{
    public function __construct(
        public int     $page = 1,
        public int     $perPage = 10,
        public ?string $searchTerm = null,
        public ?string $sortBy = null,
        public string  $sortDirection = 'ASC',
    ){}
}

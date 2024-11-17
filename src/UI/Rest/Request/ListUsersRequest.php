<?php

namespace App\UI\Rest\Request;

use App\Application\Query\ListUsers\ListUsersQuery;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;
use function strtoupper;

final readonly class ListUsersRequest{
    public function __construct(
        #[Assert\Positive]
        public int     $page,

        #[Assert\Positive]
        #[Assert\LessThanOrEqual(100)]
        public int     $perPage,

        #[Assert\Length(max: 255)]
        public ?string $searchTerm,

        #[Assert\Choice([
            'id',
            'email',
            'name',
            'created_at'
        ])]
        public ?string $sortBy,

        #[Assert\Choice([
            'ASC',
            'DESC'
        ])]
        public string  $sortDirection,
    ){}

    public static function fromRequest(Request $request,): self{
        $page          = $request->query->get('page', 1);
        $perPage       = $request->query->get('per_page', 10);
        $search        = $request->query->get('search');
        $sortBy        = $request->query->get('sort_by', 'id');
        $sortDirection = strtoupper($request->query->get('sort_direction', 'ASC'));

        return new self(page: (int) $page, perPage: (int) $perPage, searchTerm: $search, sortBy: $sortBy, sortDirection: $sortDirection);
    }

    public function toListUsersQuery(): ListUsersQuery{
        return new ListUsersQuery(page         : $this->page,
                                  perPage      : $this->perPage,
                                  searchTerm   : $this->searchTerm,
                                  sortBy       : $this->sortBy,
                                  sortDirection: $this->sortDirection
        );
    }
}
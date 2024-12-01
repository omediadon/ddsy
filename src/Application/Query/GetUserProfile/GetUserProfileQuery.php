<?php

namespace App\Application\Query\GetUserProfile;

final readonly class GetUserProfileQuery{
    public function __construct(
        public string  $userId,
        public ?string $loggedInUserId = null,
    ){}
}


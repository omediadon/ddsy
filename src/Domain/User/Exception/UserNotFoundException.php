<?php

namespace App\Domain\User\Exception;

use DomainException;

class UserNotFoundException extends DomainException{
    public function __construct(?string $message = null,){
        parent::__construct($message ?? 'No user record has been found');
    }
}

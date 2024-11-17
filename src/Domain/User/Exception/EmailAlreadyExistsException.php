<?php

namespace App\Domain\User\Exception;

use DomainException;

class EmailAlreadyExistsException extends DomainException{
    public function __construct(string $email,){
        parent::__construct(sprintf('Email "%s" is already registered', $email));
    }
}

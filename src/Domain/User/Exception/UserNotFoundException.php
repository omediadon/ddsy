<?php

namespace App\Domain\User\Exception;

use DomainException;

class UserNotFoundException extends DomainException{
    public function __construct(){
        parent::__construct('No user record has been found');
    }
}

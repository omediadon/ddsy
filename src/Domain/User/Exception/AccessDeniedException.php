<?php

namespace App\Domain\User\Exception;

use DomainException;

class AccessDeniedException extends DomainException{
    public function __construct(string $message = 'Access denied',){
        parent::__construct($message);
    }
}

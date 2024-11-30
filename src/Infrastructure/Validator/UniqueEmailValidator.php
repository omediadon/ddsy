<?php

namespace App\Infrastructure\Validator;

use App\Infrastructure\Doctrine\Repository\UserRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use function is_null;

class UniqueEmailValidator extends ConstraintValidator{
    public function __construct(private ?UserRepository $userRepository = null,){
        if(is_null($this->userRepository)){
            global $kernel;
            $this->userRepository = $kernel->getContainer()
                                           ->get(UserRepository::class);
        }
    }

    public function validate($value, Constraint $constraint,): void{
        if(!$constraint instanceof UniqueEmail){
            throw new UnexpectedTypeException($constraint, UniqueEmail::class);
        }

        // Skip validation if no email is provided
        if(null === $value || '' === $value){
            return;
        }

        // Check if email already exists in the database
        $existingUser = $this->userRepository->findByEmailString($value);

        if($existingUser !== null){
            $this->context->buildViolation($constraint->message)
                          ->addViolation();
        }
    }
}
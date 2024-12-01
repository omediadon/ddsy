<?php

namespace App\Domain\User\Services;

use App\Domain\Shared\ValueObject\Role;
use App\Domain\User;

class UserAuthorizationService{
    public function canViewProfile(User $currentUser, User $targetUser,): bool{
        // Admin can view any profile
        if($currentUser->role() === Role::ADMIN){
            return true;
        }

        // User can view their own profile
        if($currentUser->id()
                       ->equals($targetUser->id())){
            return true;
        }

        // Managers can only view customer profiles
        if($currentUser->role() === Role::MANAGER){
            return $targetUser->role() === Role::CUSTOMER;
        }

        // Customers can only view their own profile
        return false;
    }

    public function canEditProfile(User $currentUser, User $targetUser,): bool{
        // Admin can edit any profile
        if($currentUser->role() === Role::ADMIN){
            return true;
        }

        // User can edit their own profile
        return $currentUser->id()
                           ->equals($targetUser->id());
    }
}

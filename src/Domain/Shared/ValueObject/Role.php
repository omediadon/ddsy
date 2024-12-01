<?php

namespace App\Domain\Shared\ValueObject;

enum Role: string{
    case ADMIN    = 'admin';
    case MANAGER  = 'manager';
    case CUSTOMER = 'customer';

    public function hasPermission(string $permission,): bool{
        return in_array($permission, $this->permissions());
    }

    public function permissions(): array{
        return match ($this) {
            self::ADMIN    => [
                'user_manage',
                'product_manage',
                'inventory_full_access',
            ],
            self::MANAGER  => [
                'product_manage',
                'inventory_manage',
                'product_view',
            ],
            self::CUSTOMER => [
                'product_view',
            ]
        };
    }
}

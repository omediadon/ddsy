<?php

namespace App\Infrastructure\Doctrine\Type;

use App\Domain\Shared\ValueObject\Email;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

class EmailType extends Type{
    public const string NAME = 'email';

    public function getSQLDeclaration(array $column, AbstractPlatform $platform,): string{
        return $platform->getStringTypeDeclarationSQL($column);
    }

    public function convertToPHPValue($value, AbstractPlatform $platform,): ?Email{
        return $value === null ? null : Email::fromString($value);
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform,): ?string{
        return $value;
    }

    public function getName(): string{
        return self::NAME;
    }
}

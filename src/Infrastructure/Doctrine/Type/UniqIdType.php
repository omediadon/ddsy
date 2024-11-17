<?php

namespace App\Infrastructure\Doctrine\Type;

use App\Domain\Shared\ValueObject\UniqId;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

class UniqIdType extends Type{
    public const string NAME = 'uniqid';

    public function getSQLDeclaration(array $column, AbstractPlatform $platform,): string{
        return $platform->getStringTypeDeclarationSQL($column);
    }

    public function convertToPHPValue($value, AbstractPlatform $platform,): ?UniqId{
        return $value === null ? null : UniqId::fromString($value);
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform,): ?string{
        return $value;
    }

    public function getName(): string{
        return self::NAME;
    }
}

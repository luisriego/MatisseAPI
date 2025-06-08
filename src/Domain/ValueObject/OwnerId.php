<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use InvalidArgumentException;

final class OwnerId
{
    private UuidInterface $uuid;

    public function __construct(string $uuidString)
    {
        if (!Uuid::isValid($uuidString)) {
            throw new InvalidArgumentException("Invalid UUID string provided for OwnerId: {$uuidString}");
        }
        $this->uuid = Uuid::fromString($uuidString);
    }

    public static function generate(): self
    {
        return new self(Uuid::uuid4()->toString());
    }

    public function toString(): string
    {
        return $this->uuid->toString();
    }

    public function equals(OwnerId $other): bool
    {
        return $this->uuid->equals($other->uuid);
    }

    public function __toString(): string
    {
        return $this->uuid->toString();
    }
}

<?php

declare(strict_types=1);

namespace App\Application\Command;

use App\Domain\ValueObject\CondominiumId;

final class CreateUnitCommand
{
    public CondominiumId $condominiumId;
    public string $unitIdentifier; // e.g., "Apt 101"

    public function __construct(CondominiumId $condominiumId, string $unitIdentifier)
    {
        $this->condominiumId = $condominiumId;
        $this->unitIdentifier = $unitIdentifier;
    }
}

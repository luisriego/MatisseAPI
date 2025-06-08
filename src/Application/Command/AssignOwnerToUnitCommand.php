<?php

declare(strict_types=1);

namespace App\Application\Command;

use App\Domain\ValueObject\UnitId;
use App\Domain\ValueObject\OwnerId;

final class AssignOwnerToUnitCommand
{
    public UnitId $unitId;
    public OwnerId $ownerId;

    public function __construct(UnitId $unitId, OwnerId $ownerId)
    {
        $this->unitId = $unitId;
        $this->ownerId = $ownerId;
    }
}

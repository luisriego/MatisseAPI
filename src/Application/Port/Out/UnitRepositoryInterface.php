<?php

declare(strict_types=1);

namespace App\Application\Port\Out;

use App\Domain\Entity\Unit;
use App\Domain\ValueObject\UnitId;

interface UnitRepositoryInterface
{
    public function save(Unit $unit): void;
    public function findById(UnitId $unitId): ?Unit;
}

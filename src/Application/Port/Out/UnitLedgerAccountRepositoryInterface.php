<?php

declare(strict_types=1);

namespace App\Application\Port\Out;

use App\Domain\Entity\UnitLedgerAccount;
use App\Domain\ValueObject\UnitId;

interface UnitLedgerAccountRepositoryInterface
{
    public function save(UnitLedgerAccount $account): void;
    public function findByUnitId(UnitId $unitId): ?UnitLedgerAccount;
}

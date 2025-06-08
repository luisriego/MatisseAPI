<?php

declare(strict_types=1);

namespace App\Application\Query;

final class GetUnitLedgerAccountDetailsQuery
{
    public string $unitId;

    public function __construct(string $unitId)
    {
        $this->unitId = $unitId;
    }
}

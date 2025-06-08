<?php

declare(strict_types=1);

namespace App\Application\Query;

final class GetUnitStatementQuery
{
    public string $unitId;

    public function __construct(string $unitId)
    {
        $this->unitId = $unitId;
    }
}

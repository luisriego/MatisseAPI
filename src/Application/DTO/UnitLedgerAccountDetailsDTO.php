<?php

declare(strict_types=1);

namespace App\Application\DTO;

final class UnitLedgerAccountDetailsDTO
{
    public string $unitId;
    public int $balanceAmountCents;
    public string $balanceCurrencyCode;
    public string $lastUpdatedAt; // ISO8601 format typically

    public function __construct(
        string $unitId,
        int $balanceAmountCents,
        string $balanceCurrencyCode,
        string $lastUpdatedAt
    ) {
        $this->unitId = $unitId;
        $this->balanceAmountCents = $balanceAmountCents;
        $this->balanceCurrencyCode = $balanceCurrencyCode;
        $this->lastUpdatedAt = $lastUpdatedAt;
    }
}

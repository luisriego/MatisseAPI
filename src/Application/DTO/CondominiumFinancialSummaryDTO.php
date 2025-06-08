<?php

declare(strict_types=1);

namespace App\Application\DTO;

final class CondominiumFinancialSummaryDTO
{
    public string $condominiumId;
    public int $totalIncomeCents;
    public int $totalExpensesCents;
    public int $netBalanceCents;
    public string $currencyCode; // Assuming a single currency for the summary for simplicity

    public function __construct(
        string $condominiumId,
        int $totalIncomeCents,
        int $totalExpensesCents,
        int $netBalanceCents,
        string $currencyCode
    ) {
        $this->condominiumId = $condominiumId;
        $this->totalIncomeCents = $totalIncomeCents;
        $this->totalExpensesCents = $totalExpensesCents;
        $this->netBalanceCents = $netBalanceCents;
        $this->currencyCode = $currencyCode;
    }
}

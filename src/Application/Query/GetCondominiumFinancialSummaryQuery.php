<?php

declare(strict_types=1);

namespace App\Application\Query;

final class GetCondominiumFinancialSummaryQuery
{
    public string $condominiumId;
    public ?string $periodStartDate; // YYYY-MM-DD, optional
    public ?string $periodEndDate;   // YYYY-MM-DD, optional

    public function __construct(string $condominiumId, ?string $periodStartDate = null, ?string $periodEndDate = null)
    {
        $this->condominiumId = $condominiumId;
        $this->periodStartDate = $periodStartDate;
        $this->periodEndDate = $periodEndDate;
    }
}

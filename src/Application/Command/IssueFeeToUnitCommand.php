<?php

declare(strict_types=1);

namespace App\Application\Command;

use App\Domain\ValueObject\UnitId;
use App\Domain\ValueObject\FeeItemId;

final class IssueFeeToUnitCommand
{
    public UnitId $unitId;
    public FeeItemId $feeItemId;
    public int $amountCents;
    public string $currencyCode;
    public string $dueDate; // YYYY-MM-DD
    public ?string $description;

    public function __construct(
        UnitId $unitId,
        FeeItemId $feeItemId,
        int $amountCents,
        string $currencyCode,
        string $dueDate,
        ?string $description = null
    ) {
        $this->unitId = $unitId;
        $this->feeItemId = $feeItemId;
        $this->amountCents = $amountCents;
        $this->currencyCode = $currencyCode;
        $this->dueDate = $dueDate;
        $this->description = $description;
    }
}

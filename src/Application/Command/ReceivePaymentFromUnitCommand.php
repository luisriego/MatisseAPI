<?php

declare(strict_types=1);

namespace App\Application\Command;

use App\Domain\ValueObject\UnitId;

final class ReceivePaymentFromUnitCommand
{
    public UnitId $unitId;
    public int $amountCents;
    public string $currencyCode;
    public string $paymentDate; // YYYY-MM-DD
    public string $paymentMethod; // e.g., "Bank Transfer", "Cash"

    public function __construct(
        UnitId $unitId,
        int $amountCents,
        string $currencyCode,
        string $paymentDate,
        string $paymentMethod
    ) {
        $this->unitId = $unitId;
        $this->amountCents = $amountCents;
        $this->currencyCode = $currencyCode;
        $this->paymentDate = $paymentDate;
        $this->paymentMethod = $paymentMethod;
    }
}

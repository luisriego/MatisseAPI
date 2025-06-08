<?php

declare(strict_types=1);

namespace App\Application\DTO;

final class PaymentReceivedDTO
{
    public string $id; // ID of the payment (e.g., paymentId from PaymentReceivedOnUnitLedgerEvent)
    public int $amountCents;
    public string $currencyCode;
    public string $paymentDate; // YYYY-MM-DD
    public string $paymentMethod;
    public string $receivedAt; // ISO8601 DateTime with TZ (when it was recorded)

    public function __construct(
        string $id,
        int $amountCents,
        string $currencyCode,
        string $paymentDate,
        string $paymentMethod,
        string $receivedAt
    ) {
        $this->id = $id;
        $this->amountCents = $amountCents;
        $this->currencyCode = $currencyCode;
        $this->paymentDate = $paymentDate;
        $this->paymentMethod = $paymentMethod;
        $this->receivedAt = $receivedAt;
    }
}

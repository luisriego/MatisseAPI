<?php

declare(strict_types=1);

namespace App\Application\DTO;

final class FeeIssuedDTO
{
    public string $id; // ID of the fee instance (e.g., event_id from FeeAppliedToUnitLedgerEvent)
    public string $feeItemId; // Reference to the type of fee
    public ?string $description; // Description of this specific fee instance
    public int $amountCents;
    public string $currencyCode;
    public string $dueDate; // YYYY-MM-DD
    public string $issuedAt; // ISO8601 DateTime with TZ
    public string $status;

    public function __construct(
        string $id,
        string $feeItemId,
        ?string $description,
        int $amountCents,
        string $currencyCode,
        string $dueDate,
        string $issuedAt,
        string $status
    ) {
        $this->id = $id;
        $this->feeItemId = $feeItemId;
        $this->description = $description;
        $this->amountCents = $amountCents;
        $this->currencyCode = $currencyCode;
        $this->dueDate = $dueDate;
        $this->issuedAt = $issuedAt;
        $this->status = $status;
    }
}

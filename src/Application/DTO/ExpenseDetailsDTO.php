<?php

declare(strict_types=1);

namespace App\Application\DTO;

final class ExpenseDetailsDTO
{
    public string $id;
    public string $expenseCategoryId;
    // public string $expenseCategoryName; // Optional: denormalize if needed often
    public string $description;
    public int $amountCents;
    public string $currencyCode;
    public string $expenseDate; // YYYY-MM-DD
    public string $recordedAt; // ISO8601 DateTime with TZ

    public function __construct(
        string $id,
        string $expenseCategoryId,
        string $description,
        int $amountCents,
        string $currencyCode,
        string $expenseDate,
        string $recordedAt
        // string $expenseCategoryName = '' // Optional
    ) {
        $this->id = $id;
        $this->expenseCategoryId = $expenseCategoryId;
        $this->description = $description;
        $this->amountCents = $amountCents;
        $this->currencyCode = $currencyCode;
        $this->expenseDate = $expenseDate;
        $this->recordedAt = $recordedAt;
        // $this->expenseCategoryName = $expenseCategoryName;
    }
}

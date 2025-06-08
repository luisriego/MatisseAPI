<?php

declare(strict_types=1);

namespace App\Application\Command;

use App\Domain\ValueObject\CondominiumId;
use App\Domain\ValueObject\ExpenseCategoryId;

// Using Value Objects for IDs in command, strings for other data for simplicity.
// DateTime could be passed as string and converted in handler, or as DateTimeImmutable.
final class RecordCondominiumExpenseCommand
{
    public CondominiumId $condominiumId;
    public ExpenseCategoryId $expenseCategoryId;
    public string $description;
    public int $amountCents;
    public string $currencyCode;
    public string $expenseDate; // YYYY-MM-DD

    public function __construct(
        CondominiumId $condominiumId,
        ExpenseCategoryId $expenseCategoryId,
        string $description,
        int $amountCents,
        string $currencyCode,
        string $expenseDate
    ) {
        $this->condominiumId = $condominiumId;
        $this->expenseCategoryId = $expenseCategoryId;
        $this->description = $description;
        $this->amountCents = $amountCents;
        $this->currencyCode = $currencyCode;
        $this->expenseDate = $expenseDate;
    }
}

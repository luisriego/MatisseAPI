<?php

declare(strict_types=1);

namespace App\Bus\Expense;

readonly class CreateExpenseCommand
{
    public function __construct(
        public string $expenseTypeId,
        public int    $amount,
        public bool   $isRecurring,
        public array $payOnMonths,
        public ?string $description,
        public string $date,
        public string $paidFromAccountId
    ) {
    }
}

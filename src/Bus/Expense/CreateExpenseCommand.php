<?php

declare(strict_types=1);

namespace App\Bus\Expense;

readonly class CreateExpenseCommand
{
    public function __construct(
        public int $expenseTypeId,
        public int    $amount,
        public ?string $description,
        public string $date,
        public string $paidFromAccountId
    ) {
    }
}

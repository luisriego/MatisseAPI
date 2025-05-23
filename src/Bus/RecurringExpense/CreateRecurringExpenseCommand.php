<?php

namespace App\Bus\RecurringExpense;

class CreateRecurringExpenseCommand
{
    public function __construct(
        public string $description,
        public ?int $amount, // em céntimos
        public int $expenseTypeId,
        public string $accountId,
        public string $frequency,
        public int $dueDay,
        public ?array $monthsOfYear, // array de int (1-12) o null
        public \DateTimeInterface $startDate,
        public ?\DateTimeImmutable $endDate,
        public ?int $occurrencesLeft,
        public bool $isActive,
        public ?string $notes
    ) {
    }
}

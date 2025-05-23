<?php

namespace App\Bus\RecurringExpense;

class CreateRecurringExpenseCommand
{
    /**
     * @param string $description
     * @param int|null $amount
     * @param int $expenseTypeId
     * @param string $accountId
     * @param string $frequency
     * @param int $dueDay
     * @param int[]|null $monthsOfYear array of int (1-12) or null
     * @param \DateTimeInterface $startDate
     * @param \DateTimeImmutable|null $endDate
     * @param int|null $occurrencesLeft
     * @param bool $isActive
     * @param string|null $notes
     */
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

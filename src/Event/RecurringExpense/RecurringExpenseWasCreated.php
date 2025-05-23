<?php

namespace App\Event\RecurringExpense;

readonly class RecurringExpenseWasCreated
{
    public string $occurredOn;

    public function __construct(
        public string $recurringExpenseId,
        public ?int $amount,
        public string $description,
        public string $frequency,
        public string $startDate,
        public bool $isActive
    ) {
        $this->occurredOn = (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM);
    }
}

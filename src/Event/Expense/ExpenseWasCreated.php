<?php

namespace App\Event\Expense;

use App\Bus\DomainEvent;

final readonly class ExpenseWasCreated extends DomainEvent
{
    public function __construct(
        string $aggregateId,
        public int $amount,
        public ?string $description,
        public string $expenseDate,
        public string $expenseType,
        public string $paidToAccountId,
        string $eventId,
        string $occurredOn,
    ) {
        parent::__construct($aggregateId, $eventId, $occurredOn);
    }

    public static function fromPrimitives(
        string $aggregateId,
        array $body,
        string $eventId,
        string $occurredOn,
    ): self {
        return new self(
            $aggregateId,
            $body['amount'],
            $body['description'],
            $body['expenseDate'],
            $body['expenseType'],
            $body['paidToAccountId'],
            $eventId,
            $occurredOn,
        );
    }

    public static function eventName(): string
    {
        return 'expense.was_created';
    }

    public function toPrimitives(): array
    {
        return [
            'amount' => $this->amount,
            'description' => $this->description,
            'expenseDate' => $this->expenseDate,
            'expenseType' => $this->expenseType,
            'paidToAccountId' => $this->paidToAccountId,
        ];
    }
}

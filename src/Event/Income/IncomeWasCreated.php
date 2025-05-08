<?php

namespace App\Event\Income;

use App\Bus\DomainEvent;

final readonly class IncomeWasCreated extends DomainEvent
{
    public function __construct(
        string $aggregateId,
        public int $amount,
        public string $description,
        public string $incomeDate,
        public string $incomeType,
        public string $residentId,
        string $eventId,
        string $occurredOn)
    {
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
            $body['incomeDate'],
            $body['incomeType'],
            $body['residentId'],
            $eventId,
            $occurredOn,
        );
    }

    public static function eventName(): string
    {
        return 'income.was_created';
    }

    public function toPrimitives(): array
    {
        return [
            'amount' => $this->amount,
            'description' => $this->description,
            'incomeDate' => $this->incomeDate,
            'incomeType' => $this->incomeType,
            'residentId' => $this->residentId,
        ];
    }
}
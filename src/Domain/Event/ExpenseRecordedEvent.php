<?php

declare(strict_types=1);

namespace App\Domain\Event;

use App\Domain\ValueObject\CondominiumId;
use App\Domain\ValueObject\ExpenseCategoryId;
use App\Domain\ValueObject\ExpenseId;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\Currency; // For fromPayload
use DateTimeImmutable;
use InvalidArgumentException;
use Ramsey\Uuid\Uuid;

final class ExpenseRecordedEvent implements DomainEventInterface
{
    private string $eventId;
    private ExpenseId $aggregateId; // ExpenseId
    private DateTimeImmutable $occurredOn;

    private CondominiumId $condominiumId;
    private ExpenseCategoryId $expenseCategoryId;
    private string $description;
    private Money $amount;
    private DateTimeImmutable $expenseDate;

    private function __construct(
        string $eventId,
        ExpenseId $aggregateId,
        DateTimeImmutable $occurredOn,
        CondominiumId $condominiumId,
        ExpenseCategoryId $expenseCategoryId,
        string $description,
        Money $amount,
        DateTimeImmutable $expenseDate
    ) {
        $this->eventId = $eventId;
        $this->aggregateId = $aggregateId;
        $this->occurredOn = $occurredOn;
        $this->condominiumId = $condominiumId;
        $this->expenseCategoryId = $expenseCategoryId;
        $this->description = $description;
        $this->amount = $amount;
        $this->expenseDate = $expenseDate;
    }

    public static function create(
        ExpenseId $expenseId,
        CondominiumId $condominiumId,
        ExpenseCategoryId $expenseCategoryId,
        string $description,
        Money $amount,
        DateTimeImmutable $expenseDate
    ): self {
        return new self(
            Uuid::uuid4()->toString(),
            $expenseId,
            new DateTimeImmutable(), // Event occurrence date
            $condominiumId,
            $expenseCategoryId,
            $description,
            $amount,
            $expenseDate // Actual date of expense
        );
    }

    public function getEventId(): string
    {
        return $this->eventId;
    }

    public function getAggregateId(): string
    {
        return $this->aggregateId->toString();
    }

    public function getAggregateType(): string
    {
        return 'Expense';
    }

    public static function eventType(): string
    {
        return 'ExpenseRecorded';
    }

    public function getOccurredOn(): DateTimeImmutable
    {
        return $this->occurredOn;
    }

    public function getCondominiumId(): CondominiumId
    {
        return $this->condominiumId;
    }

    public function getExpenseCategoryId(): ExpenseCategoryId
    {
        return $this->expenseCategoryId;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getAmount(): Money
    {
        return $this->amount;
    }

    public function getExpenseDate(): DateTimeImmutable
    {
        return $this->expenseDate;
    }

    public function toPayload(): array
    {
        return [
            'condominiumId' => $this->condominiumId->toString(),
            'expenseCategoryId' => $this->expenseCategoryId->toString(),
            'description' => $this->description,
            'amountCents' => $this->amount->getAmount(),
            'currencyCode' => $this->amount->getCurrency()->getCode(),
            'expenseDate' => $this->expenseDate->format('Y-m-d H:i:s'), // Or just Y-m-d
        ];
    }

    public static function fromPayload(
        string $eventId,
        string $aggregateId, // This is the ExpenseId
        DateTimeImmutable $occurredOn,
        array $payload
    ): self {
        if (!isset(
            $payload['condominiumId'],
            $payload['expenseCategoryId'],
            $payload['description'],
            $payload['amountCents'],
            $payload['currencyCode'],
            $payload['expenseDate']
        )) {
            throw new InvalidArgumentException('Payload for ExpenseRecordedEvent is missing required fields.');
        }
        return new self(
            $eventId,
            new ExpenseId($aggregateId),
            $occurredOn,
            new CondominiumId($payload['condominiumId']),
            new ExpenseCategoryId($payload['expenseCategoryId']),
            $payload['description'],
            new Money((int)$payload['amountCents'], new Currency($payload['currencyCode'])),
            new DateTimeImmutable($payload['expenseDate'])
        );
    }
}

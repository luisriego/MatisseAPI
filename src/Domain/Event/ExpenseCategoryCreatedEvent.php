<?php

declare(strict_types=1);

namespace App\Domain\Event;

use App\Domain\ValueObject\CondominiumId;
use App\Domain\ValueObject\ExpenseCategoryId;
use DateTimeImmutable;
use InvalidArgumentException;
use Ramsey\Uuid\Uuid;

final class ExpenseCategoryCreatedEvent implements DomainEventInterface
{
    private string $eventId;
    private ExpenseCategoryId $aggregateId; // ExpenseCategoryId
    private DateTimeImmutable $occurredOn;

    private CondominiumId $condominiumId;
    private string $name;

    private function __construct(
        string $eventId,
        ExpenseCategoryId $aggregateId,
        DateTimeImmutable $occurredOn,
        CondominiumId $condominiumId,
        string $name
    ) {
        $this->eventId = $eventId;
        $this->aggregateId = $aggregateId;
        $this->occurredOn = $occurredOn;
        $this->condominiumId = $condominiumId;
        $this->name = $name;
    }

    public static function create(ExpenseCategoryId $expenseCategoryId, CondominiumId $condominiumId, string $name): self
    {
        return new self(
            Uuid::uuid4()->toString(),
            $expenseCategoryId,
            new DateTimeImmutable(),
            $condominiumId,
            $name
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
        return 'ExpenseCategory';
    }

    public static function eventType(): string
    {
        return 'ExpenseCategoryCreated';
    }

    public function getOccurredOn(): DateTimeImmutable
    {
        return $this->occurredOn;
    }

    public function getCondominiumId(): CondominiumId
    {
        return $this->condominiumId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function toPayload(): array
    {
        return [
            'condominiumId' => $this->condominiumId->toString(),
            'name' => $this->name,
        ];
    }

    public static function fromPayload(
        string $eventId,
        string $aggregateId, // This is the ExpenseCategoryId
        DateTimeImmutable $occurredOn,
        array $payload
    ): self {
        if (!isset($payload['condominiumId'], $payload['name'])) {
            throw new InvalidArgumentException('Payload for ExpenseCategoryCreatedEvent is missing required fields.');
        }
        return new self(
            $eventId,
            new ExpenseCategoryId($aggregateId),
            $occurredOn,
            new CondominiumId($payload['condominiumId']),
            $payload['name']
        );
    }
}

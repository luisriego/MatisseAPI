<?php

declare(strict_types=1);

namespace App\Domain\Event;

use App\Domain\ValueObject\UnitId;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\Currency; // For fromPayload
use DateTimeImmutable;
use InvalidArgumentException;
use Ramsey\Uuid\Uuid;

final class UnitLedgerAccountCreatedEvent implements DomainEventInterface
{
    private string $eventId;
    private UnitId $aggregateId; // UnitId (as UnitLedgerAccount's ID is UnitId)
    private DateTimeImmutable $occurredOn;

    private Money $initialBalance;

    private function __construct(
        string $eventId,
        UnitId $aggregateId,
        DateTimeImmutable $occurredOn,
        Money $initialBalance
    ) {
        $this->eventId = $eventId;
        $this->aggregateId = $aggregateId;
        $this->occurredOn = $occurredOn;
        $this->initialBalance = $initialBalance;
    }

    public static function create(UnitId $unitId, Money $initialBalance): self
    {
        return new self(
            Uuid::uuid4()->toString(),
            $unitId,
            new DateTimeImmutable(),
            $initialBalance
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
        return 'UnitLedgerAccount';
    }

    public static function eventType(): string
    {
        return 'UnitLedgerAccountCreated';
    }

    public function getOccurredOn(): DateTimeImmutable
    {
        return $this->occurredOn;
    }

    public function getInitialBalance(): Money
    {
        return $this->initialBalance;
    }

    public function toPayload(): array
    {
        return [
            'initialBalanceAmount' => $this->initialBalance->getAmount(),
            'initialBalanceCurrency' => $this->initialBalance->getCurrency()->getCode(),
        ];
    }

    public static function fromPayload(
        string $eventId,
        string $aggregateId, // This is the UnitId
        DateTimeImmutable $occurredOn,
        array $payload
    ): self {
        if (!isset($payload['initialBalanceAmount'], $payload['initialBalanceCurrency'])) {
            throw new InvalidArgumentException('Payload for UnitLedgerAccountCreatedEvent is missing required fields.');
        }
        return new self(
            $eventId,
            new UnitId($aggregateId),
            $occurredOn,
            new Money((int)$payload['initialBalanceAmount'], new Currency($payload['initialBalanceCurrency']))
        );
    }
}

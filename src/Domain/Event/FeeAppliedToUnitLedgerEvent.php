<?php

declare(strict_types=1);

namespace App\Domain\Event;

use App\Domain\ValueObject\FeeItemId;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\UnitId;
use App\Domain\ValueObject\Currency; // For fromPayload
use DateTimeImmutable;
use InvalidArgumentException;
use Ramsey\Uuid\Uuid;

final class FeeAppliedToUnitLedgerEvent implements DomainEventInterface
{
    private string $eventId;
    private UnitId $aggregateId; // UnitId
    private DateTimeImmutable $occurredOn;

    private FeeItemId $feeItemId;
    private Money $amount;
    private DateTimeImmutable $dueDate;
    private ?string $description;

    private function __construct(
        string $eventId,
        UnitId $aggregateId,
        DateTimeImmutable $occurredOn,
        FeeItemId $feeItemId,
        Money $amount,
        DateTimeImmutable $dueDate,
        ?string $description
    ) {
        $this->eventId = $eventId;
        $this->aggregateId = $aggregateId;
        $this->occurredOn = $occurredOn;
        $this->feeItemId = $feeItemId;
        $this->amount = $amount;
        $this->dueDate = $dueDate;
        $this->description = $description;
    }

    public static function create(
        UnitId $unitId,
        FeeItemId $feeItemId,
        Money $amount,
        DateTimeImmutable $dueDate,
        ?string $description
    ): self {
        return new self(
            Uuid::uuid4()->toString(),
            $unitId,
            new DateTimeImmutable(), // occurredOn
            $feeItemId,
            $amount,
            $dueDate,
            $description
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
        return 'FeeAppliedToUnitLedger';
    }

    public function getOccurredOn(): DateTimeImmutable
    {
        return $this->occurredOn;
    }

    public function getFeeItemId(): FeeItemId
    {
        return $this->feeItemId;
    }

    public function getAmount(): Money
    {
        return $this->amount;
    }

    public function getDueDate(): DateTimeImmutable
    {
        return $this->dueDate;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function toPayload(): array
    {
        return [
            'feeItemId' => $this->feeItemId->toString(),
            'amountCents' => $this->amount->getAmount(),
            'currencyCode' => $this->amount->getCurrency()->getCode(),
            'dueDate' => $this->dueDate->format('Y-m-d'),
            'description' => $this->description,
        ];
    }

    public static function fromPayload(
        string $eventId,
        string $aggregateId, // This is the UnitId
        DateTimeImmutable $occurredOn,
        array $payload
    ): self {
        if (!isset($payload['feeItemId'], $payload['amountCents'], $payload['currencyCode'], $payload['dueDate'])) {
            throw new InvalidArgumentException('Payload for FeeAppliedToUnitLedgerEvent is missing required fields.');
        }
        return new self(
            $eventId,
            new UnitId($aggregateId),
            $occurredOn,
            new FeeItemId($payload['feeItemId']),
            new Money((int)$payload['amountCents'], new Currency($payload['currencyCode'])),
            new DateTimeImmutable($payload['dueDate']),
            $payload['description'] ?? null
        );
    }
}

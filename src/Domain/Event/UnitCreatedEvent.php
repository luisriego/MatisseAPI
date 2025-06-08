<?php

declare(strict_types=1);

namespace App\Domain\Event;

use App\Domain\ValueObject\CondominiumId;
use App\Domain\ValueObject\UnitId;
use DateTimeImmutable;
use InvalidArgumentException;
use Ramsey\Uuid\Uuid;

final class UnitCreatedEvent implements DomainEventInterface
{
    private string $eventId;
    private UnitId $aggregateId; // UnitId
    private DateTimeImmutable $occurredOn;

    private CondominiumId $condominiumId;
    private string $identifier;

    private function __construct(
        string $eventId,
        UnitId $aggregateId, // Unit's own ID
        DateTimeImmutable $occurredOn,
        CondominiumId $condominiumId, // ID of the condominium it belongs to
        string $identifier // e.g., "Apt 101"
    ) {
        $this->eventId = $eventId;
        $this->aggregateId = $aggregateId;
        $this->occurredOn = $occurredOn;
        $this->condominiumId = $condominiumId;
        $this->identifier = $identifier;
    }

    public static function create(UnitId $unitId, CondominiumId $condominiumId, string $identifier): self
    {
        return new self(
            Uuid::uuid4()->toString(),
            $unitId,
            new DateTimeImmutable(),
            $condominiumId,
            $identifier
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
        return 'Unit';
    }

    public static function eventType(): string
    {
        return 'UnitCreated';
    }

    public function getOccurredOn(): DateTimeImmutable
    {
        return $this->occurredOn;
    }

    public function getCondominiumId(): CondominiumId
    {
        return $this->condominiumId;
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function toPayload(): array
    {
        return [
            'condominiumId' => $this->condominiumId->toString(),
            'identifier' => $this->identifier,
        ];
    }

    public static function fromPayload(
        string $eventId,
        string $aggregateId, // This is the UnitId
        DateTimeImmutable $occurredOn,
        array $payload
    ): self {
        if (!isset($payload['condominiumId'], $payload['identifier'])) {
            throw new InvalidArgumentException('Payload for UnitCreatedEvent is missing required fields.');
        }
        return new self(
            $eventId,
            new UnitId($aggregateId),
            $occurredOn,
            new CondominiumId($payload['condominiumId']),
            $payload['identifier']
        );
    }
}

<?php

declare(strict_types=1);

namespace App\Domain\Event;

use App\Domain\ValueObject\OwnerId;
use App\Domain\ValueObject\UnitId;
use DateTimeImmutable;
use InvalidArgumentException;
use Ramsey\Uuid\Uuid;

final class OwnerRemovedFromUnitEvent implements DomainEventInterface
{
    private string $eventId;
    private UnitId $aggregateId; // UnitId
    private DateTimeImmutable $occurredOn;

    private OwnerId $previousOwnerId;

    private function __construct(
        string $eventId,
        UnitId $aggregateId,
        DateTimeImmutable $occurredOn,
        OwnerId $previousOwnerId
    ) {
        $this->eventId = $eventId;
        $this->aggregateId = $aggregateId;
        $this->occurredOn = $occurredOn;
        $this->previousOwnerId = $previousOwnerId;
    }

    public static function create(UnitId $unitId, OwnerId $previousOwnerId): self
    {
        return new self(
            Uuid::uuid4()->toString(),
            $unitId,
            new DateTimeImmutable(),
            $previousOwnerId
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
        return 'OwnerRemovedFromUnit';
    }

    public function getOccurredOn(): DateTimeImmutable
    {
        return $this->occurredOn;
    }

    public function getPreviousOwnerId(): OwnerId
    {
        return $this->previousOwnerId;
    }

    public function toPayload(): array
    {
        return [
            'previousOwnerId' => $this->previousOwnerId->toString(),
        ];
    }

    public static function fromPayload(
        string $eventId,
        string $aggregateId, // This is the UnitId
        DateTimeImmutable $occurredOn,
        array $payload
    ): self {
        if (!isset($payload['previousOwnerId'])) {
            throw new InvalidArgumentException('Payload for OwnerRemovedFromUnitEvent is missing previousOwnerId.');
        }
        return new self(
            $eventId,
            new UnitId($aggregateId),
            $occurredOn,
            new OwnerId($payload['previousOwnerId'])
        );
    }
}

<?php

declare(strict_types=1);

namespace App\Domain\Event;

use App\Domain\ValueObject\OwnerId;
use App\Domain\ValueObject\UnitId;
use DateTimeImmutable;
use InvalidArgumentException;
use Ramsey\Uuid\Uuid;

final class OwnerAssignedToUnitEvent implements DomainEventInterface
{
    private string $eventId;
    private UnitId $aggregateId; // UnitId
    private DateTimeImmutable $occurredOn;

    private OwnerId $ownerId;

    private function __construct(
        string $eventId,
        UnitId $aggregateId,
        DateTimeImmutable $occurredOn,
        OwnerId $ownerId
    ) {
        $this->eventId = $eventId;
        $this->aggregateId = $aggregateId;
        $this->occurredOn = $occurredOn;
        $this->ownerId = $ownerId;
    }

    public static function create(UnitId $unitId, OwnerId $ownerId): self
    {
        return new self(
            Uuid::uuid4()->toString(),
            $unitId,
            new DateTimeImmutable(),
            $ownerId
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
        return 'OwnerAssignedToUnit';
    }

    public function getOccurredOn(): DateTimeImmutable
    {
        return $this->occurredOn;
    }

    public function getOwnerId(): OwnerId
    {
        return $this->ownerId;
    }

    public function toPayload(): array
    {
        return [
            'ownerId' => $this->ownerId->toString(),
        ];
    }

    public static function fromPayload(
        string $eventId,
        string $aggregateId, // This is the UnitId
        DateTimeImmutable $occurredOn,
        array $payload
    ): self {
        if (!isset($payload['ownerId'])) {
            throw new InvalidArgumentException('Payload for OwnerAssignedToUnitEvent is missing ownerId.');
        }
        return new self(
            $eventId,
            new UnitId($aggregateId),
            $occurredOn,
            new OwnerId($payload['ownerId'])
        );
    }
}

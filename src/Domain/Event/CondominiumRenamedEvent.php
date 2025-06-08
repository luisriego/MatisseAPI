<?php

declare(strict_types=1);

namespace App\Domain\Event;

use App\Domain\ValueObject\CondominiumId;
use DateTimeImmutable;
use InvalidArgumentException;
use Ramsey\Uuid\Uuid;

final class CondominiumRenamedEvent implements DomainEventInterface
{
    private string $eventId;
    private CondominiumId $aggregateId; // CondominiumId
    private DateTimeImmutable $occurredOn;

    private string $newName;

    private function __construct(
        string $eventId,
        CondominiumId $aggregateId,
        DateTimeImmutable $occurredOn,
        string $newName
    ) {
        $this->eventId = $eventId;
        $this->aggregateId = $aggregateId;
        $this->occurredOn = $occurredOn;
        $this->newName = $newName;
    }

    public static function create(CondominiumId $condominiumId, string $newName): self
    {
        return new self(
            Uuid::uuid4()->toString(),
            $condominiumId,
            new DateTimeImmutable(),
            $newName
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
        return 'Condominium';
    }

    public static function eventType(): string
    {
        return 'CondominiumRenamed';
    }

    public function getOccurredOn(): DateTimeImmutable
    {
        return $this->occurredOn;
    }

    public function getNewName(): string
    {
        return $this->newName;
    }

    public function toPayload(): array
    {
        return [
            'newName' => $this->newName,
        ];
    }

    public static function fromPayload(
        string $eventId,
        string $aggregateId,
        DateTimeImmutable $occurredOn,
        array $payload
    ): self {
        if (!isset($payload['newName'])) {
            throw new InvalidArgumentException('Payload for CondominiumRenamedEvent is missing newName.');
        }
        return new self(
            $eventId,
            new CondominiumId($aggregateId),
            $occurredOn,
            $payload['newName']
        );
    }
}

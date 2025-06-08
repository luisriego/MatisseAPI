<?php

declare(strict_types=1);

namespace App\Infrastructure\EventSourcing;

use App\Domain\Event\DomainEventInterface;
use DateTimeImmutable;

interface EventDeserializerInterface
{
    /**
     * Deserializes raw event data into a DomainEventInterface object.
     *
     * @param string $eventType The type of the event (e.g., 'AccountCreated').
     * @param string $aggregateType The type of the aggregate this event belongs to (e.g., 'Account').
     * @param array<string, mixed> $payload The event data.
     * @param string $eventId The UUID of the event.
     * @param string $aggregateId The UUID of the aggregate.
     * @param DateTimeImmutable $occurredOn When the event occurred.
     * @return DomainEventInterface
     * @throws \InvalidArgumentException if eventType is unknown or payload is malformed.
     */
    public function deserialize(
        string $eventType,
        string $aggregateType,
        array $payload,
        string $eventId,
        string $aggregateId,
        DateTimeImmutable $occurredOn
    ): DomainEventInterface;
}

<?php

declare(strict_types=1);

namespace App\Domain\Event;

use DateTimeImmutable;

interface DomainEventInterface
{
    public function getAggregateId(): string;
    public function getEventId(): string;
    public function getOccurredOn(): DateTimeImmutable;
    public function getAggregateType(): string; // Added for event store

    /**
     * Returns a unique string identifier for this type of event.
     * Example: "AccountCreated", "MoneyDeposited.v1"
     */
    public static function eventType(): string;

    /**
     * Returns the data payload of the event.
     * @return array<string, mixed>
     */
    public function toPayload(): array;

    /**
     * Creates an event instance from its serialized payload and metadata.
     *
     * @param string $eventId
     * @param string $aggregateId
     * @param DateTimeImmutable $occurredOn
     * @param array<string, mixed> $payload
     * @return static
     */
    public static function fromPayload(
        string $eventId,
        string $aggregateId,
        DateTimeImmutable $occurredOn,
        array $payload
    ): self;
}

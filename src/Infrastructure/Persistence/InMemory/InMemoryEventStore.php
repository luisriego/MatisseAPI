<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\InMemory;

use App\Application\Port\Out\EventStoreInterface;
use App\Domain\Event\DomainEventInterface;

final class InMemoryEventStore implements EventStoreInterface
{
    /** @var array<string, DomainEventInterface[]> */
    private array $eventsByAggregateId = [];

    public function append(DomainEventInterface ...$domainEvents): void
    {
        foreach ($domainEvents as $event) {
            $aggregateId = $event->getAggregateId();
            if (!isset($this->eventsByAggregateId[$aggregateId])) {
                $this->eventsByAggregateId[$aggregateId] = [];
            }
            $this->eventsByAggregateId[$aggregateId][] = $event;
        }
    }

    /**
     * @param string $aggregateId
     * @return DomainEventInterface[]
     */
    public function getEventsForAggregate(string $aggregateId): array
    {
        return $this->eventsByAggregateId[$aggregateId] ?? [];
    }

    // Helper method for testing or debugging, not part of the interface
    public function getAllEvents(): array
    {
        return $this->eventsByAggregateId;
    }

    public function clear(): void // Helper for testing
    {
        $this->eventsByAggregateId = [];
    }
}

<?php

declare(strict_types=1);

namespace App\Application\Port\Out;

use App\Domain\Event\DomainEventInterface;

interface EventStoreInterface
{
    /**
     * @param DomainEventInterface ...$events
     */
    public function append(DomainEventInterface ...$events): void;

    /**
     * @param string $aggregateId
     * @return DomainEventInterface[]
     */
    public function getEventsForAggregate(string $aggregateId): array;
}

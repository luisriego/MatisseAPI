<?php

declare(strict_types=1);

namespace App\Infrastructure\EventSourcing;

use App\Domain\Event\DomainEventInterface;

interface EventSerializerInterface
{
    /**
     * Serializes a domain event into an array.
     * The array should contain at least 'eventType' and 'payload'.
     *
     * @param DomainEventInterface $event
     * @return array{eventType: string, payload: array<string, mixed>}
     */
    public function serialize(DomainEventInterface $event): array;
}

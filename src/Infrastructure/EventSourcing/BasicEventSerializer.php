<?php

declare(strict_types=1);

namespace App\Infrastructure\EventSourcing;

use App\Domain\Event\DomainEventInterface;

class BasicEventSerializer implements EventSerializerInterface
{
    /**
     * {@inheritdoc}
     */
    public function serialize(DomainEventInterface $event): array
    {
        // Assumes DomainEventInterface will be updated to guarantee these methods.
        // In a more robust system, you might check if the methods exist
        // or use a specific sub-interface for events that are serializable this way.

        if (!method_exists($event, 'eventType')) {
            throw new \LogicException('Event class ' . get_class($event) . ' must implement a static eventType() method.');
        }
        if (!method_exists($event, 'toPayload')) {
            throw new \LogicException('Event class ' . get_class($event) . ' must implement a toPayload() method.');
        }

        return [
            'eventType' => $event::eventType(),
            'payload' => $event->toPayload(),
        ];
    }
}

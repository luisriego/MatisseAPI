<?php

declare(strict_types=1);

namespace App\Infrastructure\EventSourcing;

use App\Domain\Event\DomainEventInterface;
use DateTimeImmutable;
use InvalidArgumentException;

class BasicEventDeserializer implements EventDeserializerInterface
{
    /**
     * @var array<string, class-string<DomainEventInterface>>
     */
    private array $eventMap;

    /**
     * @param array<string, class-string<DomainEventInterface>> $eventMap Maps event type strings to FQCNs.
     *        Example: ['AccountCreated' => \App\Domain\Event\Account\AccountCreated::class]
     */
    public function __construct(array $eventMap)
    {
        $this->eventMap = $eventMap;
    }

    /**
     * {@inheritdoc}
     */
    public function deserialize(
        string $eventType,
        string $aggregateType, // aggregateType might be used by more advanced deserializers or logic
        array $payload,
        string $eventId,
        string $aggregateId,
        DateTimeImmutable $occurredOn
    ): DomainEventInterface {
        if (!isset($this->eventMap[$eventType])) {
            throw new InvalidArgumentException("Unknown event type '{$eventType}'. No class mapping provided.");
        }

        $eventClass = $this->eventMap[$eventType];

        if (!method_exists($eventClass, 'fromPayload')) {
            throw new \LogicException("Event class {$eventClass} must implement a static fromPayload() method.");
        }
        if (!is_subclass_of($eventClass, DomainEventInterface::class)) {
             throw new \LogicException("Event class {$eventClass} must implement DomainEventInterface.");
        }

        // The fromPayload method will be responsible for creating the event
        // with its specific properties from the payload.
        // It also receives eventId, aggregateId, occurredOn to reconstruct the base event properties.
        return $eventClass::fromPayload($eventId, $aggregateId, $occurredOn, $payload);
    }

    public function addEventMapping(string $eventType, string $eventClass): void
    {
        if (!class_exists($eventClass) || !is_subclass_of($eventClass, DomainEventInterface::class)) {
            throw new InvalidArgumentException("Provided class {$eventClass} does not exist or is not a DomainEventInterface.");
        }
        $this->eventMap[$eventType] = $eventClass;
    }
}

<?php

declare(strict_types=1);

namespace App\Domain\Event;

use App\Domain\ValueObject\Address;
use App\Domain\ValueObject\CondominiumId;
use DateTimeImmutable;
use InvalidArgumentException;
use Ramsey\Uuid\Uuid;

final class CondominiumRegisteredEvent implements DomainEventInterface
{
    private string $eventId;
    private CondominiumId $aggregateId; // CondominiumId
    private DateTimeImmutable $occurredOn;

    private string $name;
    private Address $address;

    private function __construct(
        string $eventId,
        CondominiumId $aggregateId,
        DateTimeImmutable $occurredOn,
        string $name,
        Address $address
    ) {
        $this->eventId = $eventId;
        $this->aggregateId = $aggregateId;
        $this->occurredOn = $occurredOn;
        $this->name = $name;
        $this->address = $address;
    }

    public static function create(CondominiumId $condominiumId, string $name, Address $address): self
    {
        return new self(
            Uuid::uuid4()->toString(),
            $condominiumId,
            new DateTimeImmutable(),
            $name,
            $address
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
        return 'CondominiumRegistered';
    }

    public function getOccurredOn(): DateTimeImmutable
    {
        return $this->occurredOn;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getAddress(): Address
    {
        return $this->address;
    }

    public function toPayload(): array
    {
        return [
            'name' => $this->name,
            'addressStreet' => $this->address->getStreet(),
            'addressCity' => $this->address->getCity(),
            'addressPostalCode' => $this->address->getPostalCode(),
            'addressCountry' => $this->address->getCountry(),
        ];
    }

    public static function fromPayload(
        string $eventId,
        string $aggregateId,
        DateTimeImmutable $occurredOn,
        array $payload
    ): self {
        if (!isset($payload['name'], $payload['addressStreet'], $payload['addressCity'], $payload['addressPostalCode'], $payload['addressCountry'])) {
            throw new InvalidArgumentException('Payload for CondominiumRegisteredEvent is missing required fields.');
        }
        return new self(
            $eventId,
            new CondominiumId($aggregateId),
            $occurredOn,
            $payload['name'],
            new Address(
                $payload['addressStreet'],
                $payload['addressCity'],
                $payload['addressPostalCode'],
                $payload['addressCountry']
            )
        );
    }
}

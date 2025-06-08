<?php

declare(strict_types=1);

namespace App\Domain\Event;

use App\Domain\ValueObject\Address;
use App\Domain\ValueObject\CondominiumId;
use DateTimeImmutable;
use InvalidArgumentException;
use Ramsey\Uuid\Uuid;

final class CondominiumAddressChangedEvent implements DomainEventInterface
{
    private string $eventId;
    private CondominiumId $aggregateId; // CondominiumId
    private DateTimeImmutable $occurredOn;

    private Address $newAddress;

    private function __construct(
        string $eventId,
        CondominiumId $aggregateId,
        DateTimeImmutable $occurredOn,
        Address $newAddress
    ) {
        $this->eventId = $eventId;
        $this->aggregateId = $aggregateId;
        $this->occurredOn = $occurredOn;
        $this->newAddress = $newAddress;
    }

    public static function create(CondominiumId $condominiumId, Address $newAddress): self
    {
        return new self(
            Uuid::uuid4()->toString(),
            $condominiumId,
            new DateTimeImmutable(),
            $newAddress
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
        return 'CondominiumAddressChanged';
    }

    public function getOccurredOn(): DateTimeImmutable
    {
        return $this->occurredOn;
    }

    public function getNewAddress(): Address
    {
        return $this->newAddress;
    }

    public function toPayload(): array
    {
        return [
            'newAddressStreet' => $this->newAddress->getStreet(),
            'newAddressCity' => $this->newAddress->getCity(),
            'newAddressPostalCode' => $this->newAddress->getPostalCode(),
            'newAddressCountry' => $this->newAddress->getCountry(),
        ];
    }

    public static function fromPayload(
        string $eventId,
        string $aggregateId,
        DateTimeImmutable $occurredOn,
        array $payload
    ): self {
        if (!isset($payload['newAddressStreet'], $payload['newAddressCity'], $payload['newAddressPostalCode'], $payload['newAddressCountry'])) {
            throw new InvalidArgumentException('Payload for CondominiumAddressChangedEvent is missing required fields.');
        }
        return new self(
            $eventId,
            new CondominiumId($aggregateId),
            $occurredOn,
            new Address(
                $payload['newAddressStreet'],
                $payload['newAddressCity'],
                $payload['newAddressPostalCode'],
                $payload['newAddressCountry']
            )
        );
    }
}

<?php

declare(strict_types=1);

namespace App\Domain\Event;

use App\Domain\ValueObject\OwnerId;
use DateTimeImmutable;
use InvalidArgumentException;
use Ramsey\Uuid\Uuid;

final class OwnerCreatedEvent implements DomainEventInterface
{
    private string $eventId;
    private OwnerId $aggregateId; // OwnerId
    private DateTimeImmutable $occurredOn;

    private string $name;
    private string $email;
    private string $phoneNumber;

    private function __construct(
        string $eventId,
        OwnerId $aggregateId,
        DateTimeImmutable $occurredOn,
        string $name,
        string $email,
        string $phoneNumber
    ) {
        $this->eventId = $eventId;
        $this->aggregateId = $aggregateId;
        $this->occurredOn = $occurredOn;
        $this->name = $name;
        $this->email = $email;
        $this->phoneNumber = $phoneNumber;
    }

    public static function create(OwnerId $ownerId, string $name, string $email, string $phoneNumber): self
    {
        return new self(
            Uuid::uuid4()->toString(),
            $ownerId,
            new DateTimeImmutable(),
            $name,
            $email,
            $phoneNumber
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
        return 'Owner';
    }

    public static function eventType(): string
    {
        return 'OwnerCreated';
    }

    public function getOccurredOn(): DateTimeImmutable
    {
        return $this->occurredOn;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getPhoneNumber(): string
    {
        return $this->phoneNumber;
    }

    public function toPayload(): array
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
            'phoneNumber' => $this->phoneNumber,
        ];
    }

    public static function fromPayload(
        string $eventId,
        string $aggregateId,
        DateTimeImmutable $occurredOn,
        array $payload
    ): self {
        if (!isset($payload['name'], $payload['email'], $payload['phoneNumber'])) {
            throw new InvalidArgumentException('Payload for OwnerCreatedEvent is missing required fields.');
        }
        return new self(
            $eventId,
            new OwnerId($aggregateId),
            $occurredOn,
            $payload['name'],
            $payload['email'],
            $payload['phoneNumber']
        );
    }
}

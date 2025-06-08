<?php

declare(strict_types=1);

namespace App\Domain\Event;

use App\Domain\ValueObject\OwnerId;
use DateTimeImmutable;
use InvalidArgumentException;
use Ramsey\Uuid\Uuid;

final class OwnerContactInfoUpdatedEvent implements DomainEventInterface
{
    private string $eventId;
    private OwnerId $aggregateId; // OwnerId
    private DateTimeImmutable $occurredOn;

    private string $newName;
    private string $newEmail;
    private string $newPhoneNumber;

    private function __construct(
        string $eventId,
        OwnerId $aggregateId,
        DateTimeImmutable $occurredOn,
        string $newName,
        string $newEmail,
        string $newPhoneNumber
    ) {
        $this->eventId = $eventId;
        $this->aggregateId = $aggregateId;
        $this->occurredOn = $occurredOn;
        $this->newName = $newName;
        $this->newEmail = $newEmail;
        $this->newPhoneNumber = $newPhoneNumber;
    }

    public static function create(OwnerId $ownerId, string $newName, string $newEmail, string $newPhoneNumber): self
    {
        return new self(
            Uuid::uuid4()->toString(),
            $ownerId,
            new DateTimeImmutable(),
            $newName,
            $newEmail,
            $newPhoneNumber
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
        return 'OwnerContactInfoUpdated';
    }

    public function getOccurredOn(): DateTimeImmutable
    {
        return $this->occurredOn;
    }

    public function getNewName(): string
    {
        return $this->newName;
    }

    public function getNewEmail(): string
    {
        return $this->newEmail;
    }

    public function getNewPhoneNumber(): string
    {
        return $this->newPhoneNumber;
    }

    public function toPayload(): array
    {
        return [
            'newName' => $this->newName,
            'newEmail' => $this->newEmail,
            'newPhoneNumber' => $this->newPhoneNumber,
        ];
    }

    public static function fromPayload(
        string $eventId,
        string $aggregateId,
        DateTimeImmutable $occurredOn,
        array $payload
    ): self {
        if (!isset($payload['newName'], $payload['newEmail'], $payload['newPhoneNumber'])) {
            throw new InvalidArgumentException('Payload for OwnerContactInfoUpdatedEvent is missing required fields.');
        }
        return new self(
            $eventId,
            new OwnerId($aggregateId),
            $occurredOn,
            $payload['newName'],
            $payload['newEmail'],
            $payload['newPhoneNumber']
        );
    }
}

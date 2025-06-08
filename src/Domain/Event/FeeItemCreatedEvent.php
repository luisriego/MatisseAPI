<?php

declare(strict_types=1);

namespace App\Domain\Event;

use App\Domain\ValueObject\CondominiumId;
use App\Domain\ValueObject\FeeItemId;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\Currency; // For fromPayload
use DateTimeImmutable;
use InvalidArgumentException;
use Ramsey\Uuid\Uuid;

final class FeeItemCreatedEvent implements DomainEventInterface
{
    private string $eventId;
    private FeeItemId $aggregateId; // FeeItemId
    private DateTimeImmutable $occurredOn;

    private CondominiumId $condominiumId;
    private string $description;
    private Money $defaultAmount;

    private function __construct(
        string $eventId,
        FeeItemId $aggregateId,
        DateTimeImmutable $occurredOn,
        CondominiumId $condominiumId,
        string $description,
        Money $defaultAmount
    ) {
        $this->eventId = $eventId;
        $this->aggregateId = $aggregateId;
        $this->occurredOn = $occurredOn;
        $this->condominiumId = $condominiumId;
        $this->description = $description;
        $this->defaultAmount = $defaultAmount;
    }

    public static function create(FeeItemId $feeItemId, CondominiumId $condominiumId, string $description, Money $defaultAmount): self
    {
        return new self(
            Uuid::uuid4()->toString(),
            $feeItemId,
            new DateTimeImmutable(),
            $condominiumId,
            $description,
            $defaultAmount
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
        return 'FeeItem';
    }

    public static function eventType(): string
    {
        return 'FeeItemCreated';
    }

    public function getOccurredOn(): DateTimeImmutable
    {
        return $this->occurredOn;
    }

    public function getCondominiumId(): CondominiumId
    {
        return $this->condominiumId;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getDefaultAmount(): Money
    {
        return $this->defaultAmount;
    }

    public function toPayload(): array
    {
        return [
            'condominiumId' => $this->condominiumId->toString(),
            'description' => $this->description,
            'defaultAmount' => $this->defaultAmount->getAmount(),
            'defaultCurrency' => $this->defaultAmount->getCurrency()->getCode(),
        ];
    }

    public static function fromPayload(
        string $eventId,
        string $aggregateId, // This is the FeeItemId
        DateTimeImmutable $occurredOn,
        array $payload
    ): self {
        if (!isset($payload['condominiumId'], $payload['description'], $payload['defaultAmount'], $payload['defaultCurrency'])) {
            throw new InvalidArgumentException('Payload for FeeItemCreatedEvent is missing required fields.');
        }
        return new self(
            $eventId,
            new FeeItemId($aggregateId),
            $occurredOn,
            new CondominiumId($payload['condominiumId']),
            $payload['description'],
            new Money((int)$payload['defaultAmount'], new Currency($payload['defaultCurrency']))
        );
    }
}

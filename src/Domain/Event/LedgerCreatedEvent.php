<?php

declare(strict_types=1);

namespace App\Domain\Event;

use App\Domain\ValueObject\CondominiumId;
use App\Domain\ValueObject\LedgerId;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\Currency; // For fromPayload
use DateTimeImmutable;
use InvalidArgumentException;
use Ramsey\Uuid\Uuid;

final class LedgerCreatedEvent implements DomainEventInterface
{
    private string $eventId;
    private LedgerId $aggregateId; // LedgerId
    private DateTimeImmutable $occurredOn;

    private CondominiumId $condominiumId;
    private string $name;
    private Money $initialBalance;

    private function __construct(
        string $eventId,
        LedgerId $aggregateId,
        DateTimeImmutable $occurredOn,
        CondominiumId $condominiumId,
        string $name,
        Money $initialBalance
    ) {
        $this->eventId = $eventId;
        $this->aggregateId = $aggregateId;
        $this->occurredOn = $occurredOn;
        $this->condominiumId = $condominiumId;
        $this->name = $name;
        $this->initialBalance = $initialBalance;
    }

    public static function create(LedgerId $ledgerId, CondominiumId $condominiumId, string $name, Money $initialBalance): self
    {
        return new self(
            Uuid::uuid4()->toString(),
            $ledgerId,
            new DateTimeImmutable(),
            $condominiumId,
            $name,
            $initialBalance
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
        return 'Ledger';
    }

    public static function eventType(): string
    {
        return 'LedgerCreated';
    }

    public function getOccurredOn(): DateTimeImmutable
    {
        return $this->occurredOn;
    }

    public function getCondominiumId(): CondominiumId
    {
        return $this->condominiumId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getInitialBalance(): Money
    {
        return $this->initialBalance;
    }

    public function toPayload(): array
    {
        return [
            'condominiumId' => $this->condominiumId->toString(),
            'name' => $this->name,
            'initialBalanceAmount' => $this->initialBalance->getAmount(),
            'initialBalanceCurrency' => $this->initialBalance->getCurrency()->getCode(),
        ];
    }

    public static function fromPayload(
        string $eventId,
        string $aggregateId, // This is the LedgerId
        DateTimeImmutable $occurredOn,
        array $payload
    ): self {
        if (!isset($payload['condominiumId'], $payload['name'], $payload['initialBalanceAmount'], $payload['initialBalanceCurrency'])) {
            throw new InvalidArgumentException('Payload for LedgerCreatedEvent is missing required fields.');
        }
        return new self(
            $eventId,
            new LedgerId($aggregateId),
            $occurredOn,
            new CondominiumId($payload['condominiumId']),
            $payload['name'],
            new Money((int)$payload['initialBalanceAmount'], new Currency($payload['initialBalanceCurrency']))
        );
    }
}

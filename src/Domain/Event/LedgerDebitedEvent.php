<?php

declare(strict_types=1);

namespace App\Domain\Event;

use App\Domain\ValueObject\LedgerId;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\TransactionId;
use App\Domain\ValueObject\Currency; // For fromPayload
use DateTimeImmutable;
use InvalidArgumentException;
use Ramsey\Uuid\Uuid;

final class LedgerDebitedEvent implements DomainEventInterface
{
    private string $eventId;
    private LedgerId $aggregateId; // LedgerId
    private DateTimeImmutable $occurredOn;

    private Money $amount;
    private TransactionId $transactionId;
    private string $description; // Added description

    private function __construct(
        string $eventId,
        LedgerId $aggregateId,
        DateTimeImmutable $occurredOn,
        Money $amount,
        TransactionId $transactionId,
        string $description
    ) {
        $this->eventId = $eventId;
        $this->aggregateId = $aggregateId;
        $this->occurredOn = $occurredOn;
        $this->amount = $amount;
        $this->transactionId = $transactionId;
        $this->description = $description;
    }

    public static function create(LedgerId $ledgerId, Money $amount, TransactionId $transactionId, string $description): self
    {
        return new self(
            Uuid::uuid4()->toString(),
            $ledgerId,
            new DateTimeImmutable(),
            $amount,
            $transactionId,
            $description
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
        return 'LedgerDebited';
    }

    public function getOccurredOn(): DateTimeImmutable
    {
        return $this->occurredOn;
    }

    public function getAmount(): Money
    {
        return $this->amount;
    }

    public function getTransactionId(): TransactionId
    {
        return $this->transactionId;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function toPayload(): array
    {
        return [
            'amount' => $this->amount->getAmount(),
            'currency' => $this->amount->getCurrency()->getCode(),
            'transactionId' => $this->transactionId->toString(),
            'description' => $this->description,
        ];
    }

    public static function fromPayload(
        string $eventId,
        string $aggregateId, // This is the LedgerId
        DateTimeImmutable $occurredOn,
        array $payload
    ): self {
        if (!isset($payload['amount'], $payload['currency'], $payload['transactionId'], $payload['description'])) {
            throw new InvalidArgumentException('Payload for LedgerDebitedEvent is missing required fields.');
        }
        return new self(
            $eventId,
            new LedgerId($aggregateId),
            $occurredOn,
            new Money((int)$payload['amount'], new Currency($payload['currency'])),
            new TransactionId($payload['transactionId']),
            $payload['description']
        );
    }
}

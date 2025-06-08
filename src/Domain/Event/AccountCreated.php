<?php

declare(strict_types=1);

namespace App\Domain\Event;

use App\Domain\ValueObject\AccountId;
use App\Domain\ValueObject\CustomerId;
use App\Domain\ValueObject\Money;
use DateTimeImmutable;
use DateTimeInterface;
use Ramsey\Uuid\Uuid;
// Removed redundant DateTimeInterface import

final class AccountCreated implements DomainEventInterface
{
    private string $eventId;
    // Add aggregateType, potentially set in constructor or getter returns fixed value
    private AccountId $accountId;
    private CustomerId $customerId;
    private Money $initialBalance;
    private DateTimeImmutable $occurredOn;

    // Private constructor for fromPayload to use
    private function __construct(
        string $eventId,
        AccountId $accountId,
        CustomerId $customerId,
        Money $initialBalance,
        DateTimeImmutable $occurredOn
    ) {
        $this->eventId = $eventId;
        $this->accountId = $accountId;
        $this->customerId = $customerId;
        $this->initialBalance = $initialBalance;
        $this->occurredOn = $occurredOn;
    }

    // Public factory for new event creation
    public static function create(AccountId $accountId, CustomerId $customerId, Money $initialBalance): self
    {
        return new self(
            Uuid::uuid4()->toString(),
            $accountId,
            $customerId,
            $initialBalance,
            new DateTimeImmutable()
        );
    }

    public function getAggregateId(): string
    {
        return $this->accountId->toString();
    }

    public function getAggregateType(): string
    {
        return 'Account'; // Or a constant
    }

    public static function eventType(): string
    {
        return 'AccountCreated'; // Or a constant
    }

    public function getEventId(): string
    {
        return $this->eventId;
    }

    public function getOccurredOn(): DateTimeImmutable
    {
        return $this->occurredOn;
    }

    public function getAccountId(): AccountId
    {
        return $this->accountId;
    }

    public function getCustomerId(): CustomerId
    {
        return $this->customerId;
    }

    public function getInitialBalance(): Money
    {
        return $this->initialBalance;
    }

    public function toPayload(): array
    {
        return [
            // eventId, aggregateId, occurredOn are usually not part of the payload itself
            // as they are metadata stored in separate DB columns or handled by the event store.
            // The payload should contain the event-specific data.
            'customerId' => $this->customerId->toString(),
            'initialBalanceAmount' => $this->initialBalance->getAmount(),
            'initialBalanceCurrency' => $this->initialBalance->getCurrency()->getCode(),
        ];
    }

    public static function fromPayload(
        string $eventId,
        string $aggregateId,
        DateTimeImmutable $occurredOn,
        array $payload
    ): self {
        // Validation of payload keys should be done here
        if (!isset($payload['customerId'], $payload['initialBalanceAmount'], $payload['initialBalanceCurrency'])) {
            throw new \InvalidArgumentException('Payload for AccountCreated is missing required fields.');
        }

        return new self(
            $eventId,
            new AccountId($aggregateId), // AggregateId from metadata is the AccountId for this event
            new CustomerId($payload['customerId']),
            new Money((int)$payload['initialBalanceAmount'], new Currency($payload['initialBalanceCurrency'])),
            $occurredOn
        );
    }
}

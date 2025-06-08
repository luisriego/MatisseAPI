<?php

declare(strict_types=1);

namespace App\Domain\Event;

use App\Domain\ValueObject\AccountId;
use App\Domain\ValueObject\Money;
use DateTimeImmutable;
use DateTimeInterface;
use Ramsey\Uuid\Uuid;
// Redundant imports for AccountId and Money were here, ensure Currency is kept.
use App\Domain\ValueObject\Currency;

final class WithdrawalFailedDueToInsufficientFunds implements DomainEventInterface
{
    private string $eventId;
    private AccountId $accountId;
    private Money $attemptedAmount;
    private Money $currentBalance;
    private DateTimeImmutable $occurredOn;

    private function __construct(
        string $eventId,
        AccountId $accountId,
        Money $attemptedAmount,
        Money $currentBalance,
        DateTimeImmutable $occurredOn
    ) {
        $this->eventId = $eventId;
        $this->accountId = $accountId;
        $this->attemptedAmount = $attemptedAmount;
        $this->currentBalance = $currentBalance;
        $this->occurredOn = $occurredOn;
    }

    public static function create(AccountId $accountId, Money $attemptedAmount, Money $currentBalance): self
    {
        return new self(
            Uuid::uuid4()->toString(),
            $accountId,
            $attemptedAmount,
            $currentBalance,
            new DateTimeImmutable()
        );
    }

    public function getAggregateId(): string
    {
        return $this->accountId->toString();
    }

    public function getAggregateType(): string
    {
        return 'Account';
    }

    public static function eventType(): string
    {
        return 'WithdrawalFailedDueToInsufficientFunds';
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

    public function getAttemptedAmount(): Money
    {
        return $this->attemptedAmount;
    }

    public function getCurrentBalance(): Money
    {
        return $this->currentBalance;
    }

    public function toPayload(): array
    {
        return [
            'attemptedAmount' => $this->attemptedAmount->getAmount(),
            'attemptedCurrency' => $this->attemptedAmount->getCurrency()->getCode(),
            'currentBalanceAmount' => $this->currentBalance->getAmount(),
            'currentBalanceCurrency' => $this->currentBalance->getCurrency()->getCode(),
        ];
    }

    public static function fromPayload(
        string $eventId,
        string $aggregateId,
        DateTimeImmutable $occurredOn,
        array $payload
    ): self {
        if (!isset(
            $payload['attemptedAmount'],
            $payload['attemptedCurrency'],
            $payload['currentBalanceAmount'],
            $payload['currentBalanceCurrency']
        )) {
            throw new \InvalidArgumentException('Payload for WithdrawalFailedDueToInsufficientFunds is missing required fields.');
        }

        return new self(
            $eventId,
            new AccountId($aggregateId),
            new Money((int)$payload['attemptedAmount'], new Currency($payload['attemptedCurrency'])),
            new Money((int)$payload['currentBalanceAmount'], new Currency($payload['currentBalanceCurrency'])),
            $occurredOn
        );
    }
}

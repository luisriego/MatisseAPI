<?php

declare(strict_types=1);

namespace App\Domain\Event;

use App\Domain\ValueObject\AccountId;
use App\Domain\ValueObject\Money;
use DateTimeImmutable;
use DateTimeInterface;
use Ramsey\Uuid\Uuid;
// Redundant imports for AccountId and Money were here, ensure Currency is kept if needed by fromPayload.
// AccountId and Money are already imported above.
use App\Domain\ValueObject\Currency;


final class MoneyDeposited implements DomainEventInterface
{
    private string $eventId;
    private AccountId $accountId;
    private Money $amountDeposited;
    private Money $newBalance;
    private DateTimeImmutable $occurredOn;

    private function __construct(
        string $eventId,
        AccountId $accountId,
        Money $amountDeposited,
        Money $newBalance,
        DateTimeImmutable $occurredOn
    ) {
        $this->eventId = $eventId;
        $this->accountId = $accountId;
        $this->amountDeposited = $amountDeposited;
        $this->newBalance = $newBalance;
        $this->occurredOn = $occurredOn;
    }

    public static function create(AccountId $accountId, Money $amountDeposited, Money $newBalance): self
    {
        return new self(
            Uuid::uuid4()->toString(),
            $accountId,
            $amountDeposited,
            $newBalance,
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
        return 'MoneyDeposited';
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

    public function getAmountDeposited(): Money
    {
        return $this->amountDeposited;
    }

    public function getNewBalance(): Money
    {
        return $this->newBalance;
    }

    public function toPayload(): array
    {
        return [
            'amountDepositedAmount' => $this->amountDeposited->getAmount(),
            'amountDepositedCurrency' => $this->amountDeposited->getCurrency()->getCode(),
            'newBalanceAmount' => $this->newBalance->getAmount(),
            'newBalanceCurrency' => $this->newBalance->getCurrency()->getCode(),
        ];
    }

    public static function fromPayload(
        string $eventId,
        string $aggregateId,
        DateTimeImmutable $occurredOn,
        array $payload
    ): self {
        if (!isset(
            $payload['amountDepositedAmount'],
            $payload['amountDepositedCurrency'],
            $payload['newBalanceAmount'],
            $payload['newBalanceCurrency']
        )) {
            throw new \InvalidArgumentException('Payload for MoneyDeposited is missing required fields.');
        }

        return new self(
            $eventId,
            new AccountId($aggregateId),
            new Money((int)$payload['amountDepositedAmount'], new Currency($payload['amountDepositedCurrency'])),
            new Money((int)$payload['newBalanceAmount'], new Currency($payload['newBalanceCurrency'])),
            $occurredOn
        );
    }
}

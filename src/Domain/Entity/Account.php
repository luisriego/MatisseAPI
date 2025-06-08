<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\ValueObject\AccountId;
use App\Domain\ValueObject\CustomerId;
use App\Domain\ValueObject\Money;
use App\Domain\Model\AggregateRoot; // Added
use App\Domain\Event\AccountCreated; // Added
use App\Domain\Event\MoneyDeposited; // Added
use App\Domain\Event\MoneyWithdrawn; // Added
use App\Domain\Event\WithdrawalFailedDueToInsufficientFunds; // Added
use InvalidArgumentException;
use DomainException;

final class Account extends AggregateRoot // Modified
{
    private AccountId $id; // Stays as is, ID is part of the state
    private CustomerId $customerId;
    private Money $balance;

    public function __construct(AccountId $id, CustomerId $customerId, Money $initialBalance)
    {
        // Direct state change (as per simplified approach for this subtask)
        $this->id = $id;
        $this->customerId = $customerId;
        $this->balance = $initialBalance;

        // Record event
        $this->recordEvent(AccountCreated::create($id, $customerId, $initialBalance)); // Use static factory
    }

    public function getId(): AccountId
    {
        return $this->id;
    }

    public function getCustomerId(): CustomerId
    {
        return $this->customerId;
    }

    public function getBalance(): Money
    {
        return $this->balance;
    }

    public function deposit(Money $amount): void
    {
        if (!$this->balance->getCurrency()->equals($amount->getCurrency())) {
            throw new InvalidArgumentException('Cannot deposit money with a different currency.');
        }
        // Direct state change
        $this->balance = $this->balance->add($amount);
        // Record event
        $this->recordEvent(MoneyDeposited::create($this->id, $amount, $this->balance)); // Use static factory
    }

    public function withdraw(Money $amount): void
    {
        if (!$this->balance->getCurrency()->equals($amount->getCurrency())) {
            throw new InvalidArgumentException('Cannot withdraw money with a different currency.');
        }
        if ($this->balance->getAmount() < $amount->getAmount()) {
            // Record failure event
            $this->recordEvent(
                WithdrawalFailedDueToInsufficientFunds::create($this->id, $amount, $this->balance) // Use static factory
            );
            throw new DomainException('Insufficient funds.');
        }
        // Direct state change
        $this->balance = $this->balance->subtract($amount);
        // Record success event
        $this->recordEvent(MoneyWithdrawn::create($this->id, $amount, $this->balance)); // Use static factory
    }

    protected function apply(\App\Domain\Event\DomainEventInterface $event): void
    {
        // Stub implementation for obsolete Account entity.
        // No actual event application logic needed as this entity is being phased out.
        // This method is required to satisfy the AggregateRoot abstract class.
        // For example, one might match event types if this were active:
        // match ($event::eventType()) {
        //     AccountCreated::eventType() => $this->applyAccountCreated($event),
        //     // etc.
        //     default => {}
        // };
    }
}

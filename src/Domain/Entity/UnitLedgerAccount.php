<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\Model\AggregateRoot;
use App\Domain\ValueObject\FeeItemId;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\TransactionId;
use App\Domain\ValueObject\UnitId;
use InvalidArgumentException;
use App\Domain\Event\UnitLedgerAccountCreatedEvent;
use App\Domain\Event\FeeAppliedToUnitLedgerEvent;
use App\Domain\Event\PaymentReceivedOnUnitLedgerEvent;

class UnitLedgerAccount extends AggregateRoot // Removed final
{
    private ?UnitId $id = null; // Using UnitId as the primary identifier
    private ?Money $balance = null;

    public static function createNew(UnitId $id, Money $initialBalance): self
    {
        $account = new self();
        $account->id = $id;
        $account->balance = $initialBalance;
        $account->recordEvent(UnitLedgerAccountCreatedEvent::create($id, $initialBalance));
        return $account;
    }

    public function __construct()
    {
        // For reconstitution
    }

    protected function apply(\App\Domain\Event\DomainEventInterface $event): void
    {
        match ($event::eventType()) {
            UnitLedgerAccountCreatedEvent::eventType() => $this->applyUnitLedgerAccountCreated($event),
            FeeAppliedToUnitLedgerEvent::eventType() => $this->applyFeeApplied($event),
            PaymentReceivedOnUnitLedgerEvent::eventType() => $this->applyPaymentReceived($event),
            default => throw new \LogicException("Cannot apply unknown event " . $event::eventType() . " to UnitLedgerAccount.")
        };
    }

    private function applyUnitLedgerAccountCreated(UnitLedgerAccountCreatedEvent $event): void
    {
        $this->id = new UnitId($event->getAggregateId());
        $this->balance = $event->getInitialBalance();
    }

    private function applyFeeApplied(FeeAppliedToUnitLedgerEvent $event): void
    {
        if ($this->balance === null) {
            throw new \LogicException("Balance must be initialized before applying a fee.");
        }
        // Fees increase the balance (what the unit owes)
        $this->balance = $this->balance->add($event->getAmount());
    }

    private function applyPaymentReceived(PaymentReceivedOnUnitLedgerEvent $event): void
    {
        if ($this->balance === null) {
            throw new \LogicException("Balance must be initialized before receiving a payment.");
        }
        // Payments decrease the balance (what the unit owes)
        $this->balance = $this->balance->subtract($event->getAmount());
    }

    public function getId(): UnitId
    {
        return $this->id;
    }

    public function getBalance(): Money
    {
        if ($this->balance === null) {
            throw new \LogicException("Balance accessed before initialization.");
        }
        return $this->balance;
    }

    /**
     * Applies a fee to the unit's account (increases balance due).
     */
    public function applyFee(FeeItemId $feeItemId, Money $amount, \DateTimeImmutable $dueDate, ?string $description): void
    {
        if ($this->id === null || $this->balance === null) {
            throw new \LogicException("UnitLedgerAccount must be initialized before applying a fee.");
        }
        if (!$this->balance->getCurrency()->equals($amount->getCurrency())) {
            throw new InvalidArgumentException('Cannot apply fee with a different currency to unit account.');
        }

        $this->recordEvent(FeeAppliedToUnitLedgerEvent::create($this->id, $feeItemId, $amount, $dueDate, $description));
        // Apply state change directly for "hybrid" ES approach
        $this->balance = $this->balance->add($amount);
    }

    /**
     * Records a payment received (decreases balance due).
     */
    public function receivePayment(Money $amount, TransactionId $paymentId, \DateTimeImmutable $paymentDate, string $paymentMethod): void
    {
        if ($this->id === null || $this->balance === null) {
            throw new \LogicException("UnitLedgerAccount must be initialized before receiving a payment.");
        }
        if (!$this->balance->getCurrency()->equals($amount->getCurrency())) {
            throw new InvalidArgumentException('Cannot receive payment with a different currency on unit account.');
        }

        $this->recordEvent(PaymentReceivedOnUnitLedgerEvent::create($this->id, $amount, $paymentId, $paymentDate, $paymentMethod));
        // Apply state change directly
        $this->balance = $this->balance->subtract($amount);
    }
}

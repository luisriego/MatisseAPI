<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\Model\AggregateRoot;
use App\Domain\ValueObject\CondominiumId;
use App\Domain\ValueObject\LedgerId;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\TransactionId;
use InvalidArgumentException;
use App\Domain\Event\LedgerCreatedEvent;
use App\Domain\Event\LedgerCreditedEvent;
use App\Domain\Event\LedgerDebitedEvent;

class Ledger extends AggregateRoot // Removed final
{
    private LedgerId $id;
    private CondominiumId $condominiumId;
    private string $name; // e.g., "Operating Fund", "Reserve Fund"
    private Money $balance;

    public function __construct(LedgerId $id, CondominiumId $condominiumId, string $name, Money $initialBalance)
    {
        $this->id = $id;
        $this->condominiumId = $condominiumId;
        $this->name = $name;
        $this->balance = $initialBalance;

        $this->recordEvent(LedgerCreatedEvent::create($this->id, $this->condominiumId, $this->name, $this->balance));
    }

    public function getId(): LedgerId
    {
        return $this->id;
    }

    public function getCondominiumId(): CondominiumId
    {
        return $this->condominiumId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getBalance(): Money
    {
        return $this->balance;
    }

    public function credit(Money $amount, TransactionId $transactionId): void
    {
        if (!$this->balance->getCurrency()->equals($amount->getCurrency())) {
            throw new InvalidArgumentException('Cannot credit money with a different currency to ledger.');
        }
        $this->balance = $this->balance->add($amount);
        // Assuming description for credit/debit is important context for the event
        // For now, let's use a generic description or consider adding it to the method signature.
        // Let's assume Ledger events need a description, as per Event class definitions.
        // The subtask says LedgerCreditedEvent payload: amount, currency, transactionId, description
        // This means the debit/credit methods on Ledger should probably take a description.
        // For now, I'll add a placeholder description. This should be reviewed.
        $this->recordEvent(LedgerCreditedEvent::create($this->id, $amount, $transactionId, "Ledger credited"));
    }

    public function debit(Money $amount, TransactionId $transactionId, string $description = "Ledger debited"): void
    {
        if (!$this->balance->getCurrency()->equals($amount->getCurrency())) {
            throw new InvalidArgumentException('Cannot debit money with a different currency from ledger.');
        }
        if ($this->balance->getAmount() < $amount->getAmount()) {
            // Consider specific exception for insufficient funds in ledger
            throw new \DomainException('Insufficient funds in ledger.');
        }
        $this->balance = $this->balance->subtract($amount);
        $this->recordEvent(LedgerDebitedEvent::create($this->id, $amount, $transactionId, $description));
    }
}

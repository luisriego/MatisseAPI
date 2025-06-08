<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\Event\DomainEventInterface;
use App\Domain\Event\ExpenseRecordedEvent;
use App\Domain\Model\AggregateRoot;
use App\Domain\ValueObject\CondominiumId;
use App\Domain\ValueObject\ExpenseCategoryId;
use App\Domain\ValueObject\ExpenseId;
use App\Domain\ValueObject\Money;
use DateTimeImmutable;

class Expense extends AggregateRoot // Removed final
{
    private ?ExpenseId $id = null;
    private ?CondominiumId $condominiumId = null;
    private ?ExpenseCategoryId $expenseCategoryId = null;
    private string $description = '';
    private ?Money $amount = null;
    private ?DateTimeImmutable $expenseDate = null;

    public static function createNew(
        ExpenseId $id,
        CondominiumId $condominiumId,
        ExpenseCategoryId $expenseCategoryId,
        string $description,
        Money $amount,
        DateTimeImmutable $expenseDate
    ): self {
        $expense = new self();
        // Note: Event is primary source of truth for state in apply method.
        // Here we record it, and apply will set the state.
        // Or, set state here and apply does the same for reconstitution.
        // For consistency with other ARs, let's set state here and apply mirrors it.
        $expense->id = $id;
        $expense->condominiumId = $condominiumId;
        $expense->expenseCategoryId = $expenseCategoryId;
        $expense->description = $description;
        $expense->amount = $amount;
        $expense->expenseDate = $expenseDate;

        $expense->recordEvent(ExpenseRecordedEvent::create(
            $id,
            $condominiumId,
            $expenseCategoryId,
            $description,
            $amount,
            $expenseDate
        ));
        return $expense;
    }

    public function __construct()
    {
        // For reconstitution via AggregateRoot::reconstituteFromHistory
    }

    protected function apply(\App\Domain\Event\DomainEventInterface $event): void
    {
        if ($event instanceof ExpenseRecordedEvent) {
            $this->applyExpenseRecorded($event);
        } else {
            throw new \LogicException("Cannot apply unknown event " . $event::eventType() . " to Expense aggregate.");
        }
    }

    private function applyExpenseRecorded(ExpenseRecordedEvent $event): void
    {
        $this->id = new ExpenseId($event->getAggregateId()); // Or $event->getExpenseId() if specific getter exists
        $this->condominiumId = $event->getCondominiumId();
        $this->expenseCategoryId = $event->getExpenseCategoryId();
        $this->description = $event->getDescription();
        $this->amount = $event->getAmount();
        $this->expenseDate = $event->getExpenseDate();
    }

    public function getId(): ?ExpenseId // Nullable until creation event is applied
    {
        return $this->id;
    }

    public function getCondominiumId(): ?CondominiumId
    {
        return $this->condominiumId;
    }

    public function getExpenseCategoryId(): ?ExpenseCategoryId
    {
        return $this->expenseCategoryId;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getAmount(): ?Money
    {
        return $this->amount;
    }

    public function getExpenseDate(): ?DateTimeImmutable
    {
        return $this->expenseDate;
    }
}

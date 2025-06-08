<?php

declare(strict_types=1);

namespace Tests\Domain\Entity;

use App\Domain\Entity\Expense;
use App\Domain\Event\ExpenseRecordedEvent;
use App\Domain\ValueObject\CondominiumId;
use App\Domain\ValueObject\ExpenseCategoryId;
use App\Domain\ValueObject\ExpenseId;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\Currency;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class ExpenseTest extends TestCase
{
    public function testCreateNewExpenseRecordsEventAndSetsProperties(): void
    {
        $expenseId = ExpenseId::generate();
        $condominiumId = CondominiumId::generate();
        $expenseCategoryId = ExpenseCategoryId::generate();
        $description = "Office Supplies";
        $amount = new Money(5000, new Currency("USD"));
        $expenseDate = new DateTimeImmutable("2024-01-15");

        $expense = Expense::createNew(
            $expenseId,
            $condominiumId,
            $expenseCategoryId,
            $description,
            $amount,
            $expenseDate
        );

        $this->assertTrue($expenseId->equals($expense->getId()));
        $this->assertTrue($condominiumId->equals($expense->getCondominiumId()));
        $this->assertTrue($expenseCategoryId->equals($expense->getExpenseCategoryId()));
        $this->assertEquals($description, $expense->getDescription());
        $this->assertTrue($amount->equals($expense->getAmount()));
        $this->assertEquals($expenseDate, $expense->getExpenseDate());

        $events = $expense->pullDomainEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(ExpenseRecordedEvent::class, $events[0]);

        /** @var ExpenseRecordedEvent $event */
        $event = $events[0];
        $this->assertEquals($expenseId->toString(), $event->getAggregateId());
        $this->assertTrue($condominiumId->equals($event->getCondominiumId()));
        $this->assertTrue($expenseCategoryId->equals($event->getExpenseCategoryId()));
        $this->assertEquals($description, $event->getDescription());
        $this->assertTrue($amount->equals($event->getAmount()));
        $this->assertEquals($expenseDate, $event->getExpenseDate());
    }

    public function testReconstituteFromHistory(): void
    {
        $expenseId = ExpenseId::generate();
        $condominiumId = CondominiumId::generate();
        $expenseCategoryId = ExpenseCategoryId::generate();
        $description = "Initial Repair";
        $amount = new Money(10000, new Currency("EUR"));
        $expenseDate = new DateTimeImmutable("2023-12-01");
        $occurredOn = new DateTimeImmutable(); // Actual event occurrence

        $event = ExpenseRecordedEvent::create(
            $expenseId,
            $condominiumId,
            $expenseCategoryId,
            $description,
            $amount,
            $expenseDate
        );
        // Manually set eventId and occurredOn if ::create doesn't allow override for testing reconstitution
        // For this test, we'll assume ::create is sufficient or we use fromPayload's logic indirectly
        // To be precise for reconstitution, we'd use fromPayload to simulate data from DB

        $reconstitutedEvent = ExpenseRecordedEvent::fromPayload(
            $event->getEventId(),
            $event->getAggregateId(),
            $event->getOccurredOn(), // Use the original event's occurredOn
            $event->toPayload() // Use the original event's payload
        );


        $expense = Expense::reconstituteFromHistory($reconstitutedEvent);

        $this->assertTrue($expenseId->equals($expense->getId()));
        $this->assertEquals($description, $expense->getDescription());
        $this->assertTrue($amount->equals($expense->getAmount()));
        $this->assertEquals($expenseDate, $expense->getExpenseDate());
        $this->assertEmpty($expense->pullDomainEvents()); // No new events from reconstitution
    }
}

<?php

declare(strict_types=1);

namespace Tests\Domain\Entity;

use App\Domain\Entity\UnitLedgerAccount;
use App\Domain\Event\FeeAppliedToUnitLedgerEvent;
use App\Domain\Event\PaymentReceivedOnUnitLedgerEvent;
use App\Domain\Event\UnitLedgerAccountCreatedEvent;
use App\Domain\ValueObject\Currency;
use App\Domain\ValueObject\FeeItemId;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\TransactionId;
use App\Domain\ValueObject\UnitId;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class UnitLedgerAccountTest extends TestCase
{
    private UnitId $unitId;
    private Currency $usd;

    protected function setUp(): void
    {
        $this->unitId = UnitId::generate();
        $this->usd = new Currency('USD');
    }

    public function testCreateNewAccount(): void
    {
        $initialBalance = new Money(1000, $this->usd); // 10.00 USD
        $account = UnitLedgerAccount::createNew($this->unitId, $initialBalance);

        $this->assertTrue($this->unitId->equals($account->getId()));
        $this->assertTrue($initialBalance->equals($account->getBalance()));

        $events = $account->pullDomainEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(UnitLedgerAccountCreatedEvent::class, $events[0]);
        /** @var UnitLedgerAccountCreatedEvent $event */
        $event = $events[0];
        $this->assertTrue($this->unitId->equals(new UnitId($event->getAggregateId())));
        $this->assertTrue($initialBalance->equals($event->getInitialBalance()));
    }

    public function testApplyFee(): void
    {
        $account = UnitLedgerAccount::createNew($this->unitId, new Money(0, $this->usd));
        $account->pullDomainEvents(); // Clear creation event

        $feeItemId = FeeItemId::generate();
        $feeAmount = new Money(5000, $this->usd); // 50.00 USD
        $dueDate = new DateTimeImmutable('2024-12-31');
        $description = "Annual Fee";

        $account->applyFee($feeItemId, $feeAmount, $dueDate, $description);

        $this->assertEquals(5000, $account->getBalance()->getAmount());
        $events = $account->pullDomainEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(FeeAppliedToUnitLedgerEvent::class, $events[0]);
        /** @var FeeAppliedToUnitLedgerEvent $event */
        $event = $events[0];
        $this->assertTrue($feeItemId->equals($event->getFeeItemId()));
        $this->assertTrue($feeAmount->equals($event->getAmount()));
        $this->assertEquals($dueDate, $event->getDueDate());
        $this->assertEquals($description, $event->getDescription());
    }

    public function testReceivePayment(): void
    {
        $account = UnitLedgerAccount::createNew($this->unitId, new Money(5000, $this->usd)); // Start with 50.00 balance
        $account->pullDomainEvents(); // Clear creation event

        $paymentAmount = new Money(2000, $this->usd); // 20.00 USD
        $paymentId = TransactionId::generate();
        $paymentDate = new DateTimeImmutable('2024-01-20');
        $paymentMethod = "Bank Transfer";

        $account->receivePayment($paymentAmount, $paymentId, $paymentDate, $paymentMethod);

        $this->assertEquals(3000, $account->getBalance()->getAmount()); // 50 - 20 = 30
        $events = $account->pullDomainEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(PaymentReceivedOnUnitLedgerEvent::class, $events[0]);
        /** @var PaymentReceivedOnUnitLedgerEvent $event */
        $event = $events[0];
        $this->assertTrue($paymentAmount->equals($event->getAmount()));
        $this->assertTrue($paymentId->equals($event->getPaymentId()));
        $this->assertEquals($paymentDate, $event->getPaymentDate());
        $this->assertEquals($paymentMethod, $event->getPaymentMethod());
    }

    public function testReconstitution(): void
    {
        $initialBalance = new Money(1000, $this->usd);
        $creationEvent = UnitLedgerAccountCreatedEvent::create($this->unitId, $initialBalance);

        $feeItemId = FeeItemId::generate();
        $feeAmount = new Money(500, $this->usd);
        $feeDueDate = new DateTimeImmutable('2024-03-01');
        $feeDescription = "Late Fee";
        // Simulate event being created after initial state
        $feeEvent = FeeAppliedToUnitLedgerEvent::create($this->unitId, $feeItemId, $feeAmount, $feeDueDate, $feeDescription);

        $paymentAmount = new Money(300, $this->usd);
        $paymentId = TransactionId::generate();
        $paymentDate = new DateTimeImmutable('2024-03-05');
        $paymentMethod = "Online";
        $paymentEvent = PaymentReceivedOnUnitLedgerEvent::create($this->unitId, $paymentAmount, $paymentId, $paymentDate, $paymentMethod);

        // Reconstitute
        $account = UnitLedgerAccount::reconstituteFromHistory($creationEvent, $feeEvent, $paymentEvent);

        $this->assertTrue($this->unitId->equals($account->getId()));
        // Expected balance: 1000 (initial) + 500 (fee) - 300 (payment) = 1200
        $this->assertEquals(1200, $account->getBalance()->getAmount());
        $this->assertEmpty($account->pullDomainEvents());
    }
}

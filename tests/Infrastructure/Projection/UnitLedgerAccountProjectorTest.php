<?php

declare(strict_types=1);

namespace Tests\Infrastructure\Projection;

use App\Domain\Event\FeeAppliedToUnitLedgerEvent;
use App\Domain\Event\PaymentReceivedOnUnitLedgerEvent;
use App\Domain\Event\UnitLedgerAccountCreatedEvent;
use App\Domain\ValueObject\Currency;
use App\Domain\ValueObject\FeeItemId;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\TransactionId;
use App\Domain\ValueObject\UnitId;
use App\Infrastructure\Projection\UnitLedgerAccountProjector;
use DateTimeImmutable;
use PDO;
use PHPUnit\Framework\TestCase;
use Tests\Integration\DatabaseTestCaseHelper;

final class UnitLedgerAccountProjectorTest extends TestCase
{
    use DatabaseTestCaseHelper;

    private UnitLedgerAccountProjector $projector;

    public static function setUpBeforeClass(): void
    {
        self::setUpBeforeClassNeedsDb();
    }

    protected function setUp(): void
    {
        $pdo = self::getPDO();
        $this->startTransaction();
        $this->projector = new UnitLedgerAccountProjector($pdo);
    }

    protected function tearDown(): void
    {
        $this->rollback();
    }

    public static function tearDownAfterClass(): void
    {
        self::$pdo = null;
    }

    public function testSupportsCorrectEvents(): void
    {
        $unitId = UnitId::generate();
        $this->assertTrue($this->projector->supports(UnitLedgerAccountCreatedEvent::create($unitId, new Money(0, new Currency("USD")))));
        $this->assertTrue($this->projector->supports(FeeAppliedToUnitLedgerEvent::create($unitId, FeeItemId::generate(), new Money(100, new Currency("USD")), new DateTimeImmutable(), "Test")));
        $this->assertTrue($this->projector->supports(PaymentReceivedOnUnitLedgerEvent::create($unitId, new Money(50, new Currency("USD")), TransactionId::generate(), new DateTimeImmutable(), "Test")));
        $this->assertFalse($this->projector->supports($this->createMock(\App\Domain\Event\DomainEventInterface::class)));
    }

    public function testProjectsUnitLedgerAccountCreatedEvent(): void
    {
        $unitId = UnitId::generate();
        $initialBalance = new Money(10000, new Currency("EUR")); // 100.00 EUR
        $event = UnitLedgerAccountCreatedEvent::create($unitId, $initialBalance);

        $this->projector->project($event);

        $stmt = self::getPDO()->prepare("SELECT * FROM unit_ledger_accounts WHERE unit_id = :unit_id");
        $stmt->execute([':unit_id' => $unitId->toString()]);
        $row = $stmt->fetch();

        $this->assertNotFalse($row);
        $this->assertEquals(10000, (int)$row['balance_amount_cents']);
        $this->assertEquals("EUR", $row['balance_currency_code']);
    }

    public function testProjectsFeeAppliedEvent(): void
    {
        $unitId = UnitId::generate();
        // 1. Create account
        $initialBalance = new Money(5000, new Currency("USD")); // 50.00 USD
        $createEvent = UnitLedgerAccountCreatedEvent::create($unitId, $initialBalance);
        $this->projector->project($createEvent);

        // 2. Apply Fee
        $feeItemId = FeeItemId::generate();
        $feeAmount = new Money(2500, new Currency("USD")); // 25.00 USD
        $feeEvent = FeeAppliedToUnitLedgerEvent::create(
            $unitId, $feeItemId, $feeAmount, new DateTimeImmutable("2024-05-01"), "Service Fee"
        );
        $this->projector->project($feeEvent);

        $stmt = self::getPDO()->prepare("SELECT balance_amount_cents FROM unit_ledger_accounts WHERE unit_id = :unit_id");
        $stmt->execute([':unit_id' => $unitId->toString()]);
        $row = $stmt->fetch();

        $this->assertNotFalse($row);
        // Expected: 5000 (initial) + 2500 (fee) = 7500
        $this->assertEquals(7500, (int)$row['balance_amount_cents']);
    }

    public function testProjectsPaymentReceivedEvent(): void
    {
        $unitId = UnitId::generate();
        // 1. Create account with some balance (e.g. from a previous fee)
        $initialBalance = new Money(7500, new Currency("USD")); // 75.00 USD
        $createEvent = UnitLedgerAccountCreatedEvent::create($unitId, $initialBalance);
        $this->projector->project($createEvent);

        // 2. Receive Payment
        $paymentAmount = new Money(3000, new Currency("USD")); // 30.00 USD
        $paymentEvent = PaymentReceivedOnUnitLedgerEvent::create(
            $unitId, $paymentAmount, TransactionId::generate(), new DateTimeImmutable("2024-05-10"), "Online Payment"
        );
        $this->projector->project($paymentEvent);

        $stmt = self::getPDO()->prepare("SELECT balance_amount_cents FROM unit_ledger_accounts WHERE unit_id = :unit_id");
        $stmt->execute([':unit_id' => $unitId->toString()]);
        $row = $stmt->fetch();

        $this->assertNotFalse($row);
        // Expected: 7500 (initial) - 3000 (payment) = 4500
        $this->assertEquals(4500, (int)$row['balance_amount_cents']);
    }
}

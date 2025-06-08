<?php

declare(strict_types=1);

namespace Tests\Infrastructure\Projection;

use App\Domain\Event\PaymentReceivedOnUnitLedgerEvent;
use App\Domain\ValueObject\Currency;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\TransactionId;
use App\Domain\ValueObject\UnitId;
use App\Infrastructure\Projection\PaymentProjector;
use DateTimeImmutable;
use PDO;
use PHPUnit\Framework\TestCase;
use Tests\Integration\DatabaseTestCaseHelper;

final class PaymentProjectorTest extends TestCase
{
    use DatabaseTestCaseHelper;

    // private PDO $pdo; // Removed
    private PaymentProjector $projector;

    public static function setUpBeforeClass(): void
    {
        self::setUpBeforeClassNeedsDb();
    }

    protected function setUp(): void
    {
        $pdo = self::getPDO();
        $this->startTransaction();
        $this->projector = new PaymentProjector($pdo);
    }

    protected function tearDown(): void
    {
        $this->rollback();
    }

    public static function tearDownAfterClass(): void
    {
        self::$pdo = null;
    }

    public function testProjectPaymentReceivedEvent(): void
    {
        $unitId = UnitId::generate();
        $paymentId = TransactionId::generate(); // This is the ID for the payment itself
        // We'd need to pre-insert Unit if FK constraints are active and tested.

        $event = PaymentReceivedOnUnitLedgerEvent::create(
            $unitId, // Aggregate ID (UnitLedgerAccount)
            new Money(7500, new Currency("EUR")), // 75.00 EUR
            $paymentId, // ID of this payment transaction
            new DateTimeImmutable("2024-02-20"), // Payment date
            "Bank Deposit" // Payment method
        );

        $this->projector->project($event);

        // The projector uses $event->getPaymentId() as the PK for payments_received table
        $stmt = self::getPDO()->prepare("SELECT * FROM payments_received WHERE id = :id"); // Use self::getPDO()
        $stmt->execute([':id' => $paymentId->toString()]);
        $row = $stmt->fetch();

        $this->assertNotFalse($row);
        $this->assertEquals($paymentId->toString(), $row['id']);
        $this->assertEquals($unitId->toString(), $row['unit_id']);
        $this->assertEquals(7500, (int)$row['amount_cents']);
        $this->assertEquals("EUR", $row['currency_code']);
        $this->assertEquals("2024-02-20", $row['payment_date']);
        $this->assertEquals("Bank Deposit", $row['payment_method']);
        // $this->assertEquals($event->getOccurredOn()->format('Y-m-d H:i:s'), substr($row['received_at'],0,19));
    }
}

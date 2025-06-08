<?php

declare(strict_types=1);

namespace Tests\Infrastructure\Projection;

use App\Domain\Event\FeeAppliedToUnitLedgerEvent;
use App\Domain\ValueObject\Currency;
use App\Domain\ValueObject\FeeItemId;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\UnitId;
use App\Infrastructure\Projection\FeeProjector;
use DateTimeImmutable;
use PDO;
use PHPUnit\Framework\TestCase;
use Tests\Integration\DatabaseTestCaseHelper;

final class FeeProjectorTest extends TestCase
{
    use DatabaseTestCaseHelper;

    // private PDO $pdo; // Removed
    private FeeProjector $projector;

    public static function setUpBeforeClass(): void
    {
        self::setUpBeforeClassNeedsDb();
    }

    protected function setUp(): void
    {
        $pdo = self::getPDO();
        $this->startTransaction();
        $this->projector = new FeeProjector($pdo);
    }

    protected function tearDown(): void
    {
        $this->rollback();
    }

    public static function tearDownAfterClass(): void
    {
        self::$pdo = null;
    }

    public function testProjectFeeAppliedEvent(): void
    {
        $unitId = UnitId::generate();
        $feeItemId = FeeItemId::generate();
        // We'd need to pre-insert Unit and FeeItem if FK constraints are active and tested.

        $event = FeeAppliedToUnitLedgerEvent::create(
            $unitId,
            $feeItemId,
            new Money(12000, new Currency("CAD")), // 120.00 CAD
            new DateTimeImmutable("2024-03-15"), // Due date
            "Special Assessment Fee"
        );

        $this->projector->project($event);

        $stmt = self::getPDO()->prepare("SELECT * FROM fees_issued WHERE id = :id"); // Use self::getPDO()
        $stmt->execute([':id' => $event->getEventId()]); // Projector uses eventId as PK
        $row = $stmt->fetch();

        $this->assertNotFalse($row);
        $this->assertEquals($event->getEventId(), $row['id']);
        $this->assertEquals($unitId->toString(), $row['unit_id']);
        $this->assertEquals($feeItemId->toString(), $row['fee_item_id']);
        $this->assertEquals("Special Assessment Fee", $row['description']);
        $this->assertEquals(12000, (int)$row['amount_cents']);
        $this->assertEquals("CAD", $row['currency_code']);
        $this->assertEquals("2024-03-15", $row['due_date']);
        $this->assertEquals("PENDING", $row['status']);
        // $this->assertEquals($event->getOccurredOn()->format('Y-m-d H:i:s'), substr($row['issued_at'],0,19));
    }
}

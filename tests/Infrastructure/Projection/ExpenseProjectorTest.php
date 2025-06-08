<?php

declare(strict_types=1);

namespace Tests\Infrastructure\Projection;

use App\Domain\Event\ExpenseRecordedEvent;
use App\Domain\ValueObject\CondominiumId;
use App\Domain\ValueObject\Currency;
use App\Domain\ValueObject\ExpenseCategoryId;
use App\Domain\ValueObject\ExpenseId;
use App\Domain\ValueObject\Money;
use App\Infrastructure\Projection\ExpenseProjector;
use DateTimeImmutable;
use PDO;
use PHPUnit\Framework\TestCase;
use Tests\Integration\DatabaseTestCaseHelper;

final class ExpenseProjectorTest extends TestCase
{
    use DatabaseTestCaseHelper;

    // private PDO $pdo; // Removed, use self::$pdo from trait or assign in setUp
    private ExpenseProjector $projector;

    public static function setUpBeforeClass(): void
    {
        self::setUpBeforeClassNeedsDb();
    }

    protected function setUp(): void
    {
        // $this->pdo is no longer a class property. Use local var or self::$pdo directly.
        $pdo = self::getPDO();
        $this->startTransaction(); // Uses self::getPDO() internally from trait
        $this->projector = new ExpenseProjector($pdo);
    }

    protected function tearDown(): void
    {
        $this->rollback();
    }

    public static function tearDownAfterClass(): void
    {
        self::$pdo = null;
    }

    public function testProjectExpenseRecordedEvent(): void
    {
        $expenseId = ExpenseId::generate();
        $condoId = CondominiumId::generate();
        $categoryId = ExpenseCategoryId::generate();
        // We need to ensure these FKs exist if we want to test FK constraints,
        // but for projector unit test, we assume they would exist.
        // For now, we don't pre-insert them.

        $event = ExpenseRecordedEvent::create(
            $expenseId,
            $condoId,
            $categoryId,
            "Landscaping services for common area",
            new Money(25000, new Currency("USD")), // 250.00 USD
            new DateTimeImmutable("2024-01-20")
        );

        $this->projector->project($event);

        $stmt = self::getPDO()->prepare("SELECT * FROM expenses WHERE id = :id"); // Use self::getPDO()
        $stmt->execute([':id' => $expenseId->toString()]);
        $row = $stmt->fetch();

        $this->assertNotFalse($row);
        $this->assertEquals($expenseId->toString(), $row['id']);
        $this->assertEquals($condoId->toString(), $row['condominium_id']);
        $this->assertEquals($categoryId->toString(), $row['expense_category_id']);
        $this->assertEquals("Landscaping services for common area", $row['description']);
        $this->assertEquals(25000, (int)$row['amount_cents']);
        $this->assertEquals("USD", $row['currency_code']);
        $this->assertEquals("2024-01-20", $row['expense_date']); // Date part only
        // $this->assertEquals($event->getOccurredOn()->format('Y-m-d H:i:s.u'), substr($row['recorded_at'],0,21)); // Check timestamp, ignore TZ for simplicity
    }
}

<?php

declare(strict_types=1);

namespace Tests\Infrastructure\Persistence\Postgres;

use App\Domain\Entity\Expense;
use App\Domain\ValueObject\CondominiumId;
use App\Domain\ValueObject\ExpenseCategoryId;
use App\Domain\ValueObject\ExpenseId;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\Currency;
use App\Infrastructure\EventSourcing\BasicEventDeserializer;
use App\Infrastructure\EventSourcing\BasicEventSerializer;
use App\Infrastructure\Persistence\Postgres\PostgresEventStore;
use App\Infrastructure\Persistence\Postgres\PostgresExpenseRepository;
use App\Infrastructure\Projection\ProjectionManager; // Mocked
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Tests\Integration\DatabaseTestCaseHelper; // Our helper trait

final class PostgresExpenseRepositoryTest extends TestCase
{
    use DatabaseTestCaseHelper;

    private PostgresEventStore $eventStore;
    private PostgresExpenseRepository $expenseRepository;
    private BasicEventDeserializer $eventDeserializer;

    public static function setUpBeforeClass(): void
    {
        self::setUpBeforeClassNeedsDb(); // Create DB and schema if not exists
    }

    protected function setUp(): void
    {
        $pdo = self::getPDO();
        $this->startTransaction(); // Start transaction for each test

        // Setup Event Store with real serializer/deserializer
        // For deserializer, we need to map event types we expect to retrieve
        $this->eventDeserializer = new BasicEventDeserializer([
             \App\Domain\Event\ExpenseRecordedEvent::eventType() => \App\Domain\Event\ExpenseRecordedEvent::class,
        ]);
        $this->eventStore = new PostgresEventStore($pdo, new BasicEventSerializer(), $this->eventDeserializer);

        // Mock ProjectionManager as its detailed testing is not the focus here
        $projectionManagerMock = $this->createMock(ProjectionManager::class);

        $this->expenseRepository = new PostgresExpenseRepository($this->eventStore, $projectionManagerMock);
    }

    protected function tearDown(): void
    {
        $this->rollback(); // Rollback transaction
    }

    public static function tearDownAfterClass(): void
    {
        self::$pdo = null; // Close connection
    }


    public function testSaveAndFindById(): void
    {
        $expenseId = ExpenseId::generate();
        $condoId = CondominiumId::generate();
        $categoryId = ExpenseCategoryId::generate();
        $description = "Test Expense";
        $amount = new Money(12000, new Currency("EUR"));
        $expenseDate = new DateTimeImmutable("2024-07-01");

        $expense = Expense::createNew($expenseId, $condoId, $categoryId, $description, $amount, $expenseDate);
        $this->expenseRepository->save($expense);

        $foundExpense = $this->expenseRepository->findById($expenseId);

        $this->assertNotNull($foundExpense);
        $this->assertTrue($expenseId->equals($foundExpense->getId()));
        $this->assertTrue($condoId->equals($foundExpense->getCondominiumId()));
        $this->assertTrue($categoryId->equals($foundExpense->getExpenseCategoryId()));
        $this->assertEquals($description, $foundExpense->getDescription());
        $this->assertTrue($amount->equals($foundExpense->getAmount()));
        $this->assertEquals($expenseDate->format('Y-m-d'), $foundExpense->getExpenseDate()->format('Y-m-d'));
    }

    public function testFindByIdReturnsNullForNonExistentExpense(): void
    {
        $nonExistentId = ExpenseId::generate();
        $foundExpense = $this->expenseRepository->findById($nonExistentId);
        $this->assertNull($foundExpense);
    }
}

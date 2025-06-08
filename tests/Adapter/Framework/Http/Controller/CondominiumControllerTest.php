<?php

declare(strict_types=1);

namespace Tests\Adapter\Framework\Http\Controller;

use App\Adapter\Framework\Http\Controller\CondominiumController;
use App\Application\Command\CreateUnitCommandHandler;
use App\Application\Command\RecordCondominiumExpenseCommandHandler;
use App\Application\Command\RegisterCondominiumCommandHandler;
use App\Application\DTO\CondominiumDetailsDTO;
use App\Application\DTO\CondominiumFinancialSummaryDTO;
use App\Application\Query\GetCondominiumDetailsQueryHandler;
use App\Application\Query\GetCondominiumFinancialSummaryQueryHandler;
use App\Application\Query\GetUnitsInCondominiumQueryHandler;
use App\Domain\ValueObject\CondominiumId; // For creating test IDs
use App\Domain\ValueObject\ExpenseCategoryId; // For creating test IDs
use App\Infrastructure\EventSourcing\BasicEventDeserializer;
use App\Infrastructure\EventSourcing\BasicEventSerializer;
use App\Infrastructure\Persistence\Postgres\PostgresCondominiumRepository;
use App\Infrastructure\Persistence\Postgres\PostgresEventStore;
use App\Infrastructure\Persistence\Postgres\PostgresExpenseCategoryRepository;
use App\Infrastructure\Persistence\Postgres\PostgresExpenseRepository;
use App\Infrastructure\Persistence\Postgres\PostgresUnitRepository;
use App\Infrastructure\Projection\CondominiumProjector;
use App\Infrastructure\Projection\ExpenseProjector;
use App\Infrastructure\Projection\ProjectionManager;
use App\Infrastructure\Projection\UnitProjector; // Needed for unit creation part
use PDO;
use Psr\Log\NullLogger;
use PHPUnit\Framework\TestCase;
use Tests\Integration\DatabaseTestCaseHelper;
use App\Domain\Entity\ExpenseCategory; // To pre-populate for tests

final class CondominiumControllerTest extends TestCase
{
    use DatabaseTestCaseHelper;

    // private static PDO $pdo; // Removed, rely on trait's property
    private CondominiumController $controller;
    private BasicEventDeserializer $eventDeserializer;
    private ProjectionManager $projectionManager;

    // Repositories used by handlers
    private PostgresCondominiumRepository $condoRepository;
    private PostgresUnitRepository $unitRepository;
    private PostgresExpenseRepository $expenseRepository;
    private PostgresExpenseCategoryRepository $expenseCategoryRepository;


    public static function setUpBeforeClass(): void
    {
        self::setUpBeforeClassNeedsDb();
        self::$pdo = self::getPDO(); // Get PDO connection via helper
        // Ensure schema is applied if not already
        // self::applySchema(); // This is now handled by setUpBeforeClassNeedsDb
    }

    protected function setUp(): void
    {
        $this->startTransaction(); // Start transaction for each test

        $eventSerializer = new BasicEventSerializer();
        // Setup Event Deserializer with all event types used by tested aggregates
        $this->eventDeserializer = new BasicEventDeserializer([
            \App\Domain\Event\CondominiumRegisteredEvent::eventType() => \App\Domain\Event\CondominiumRegisteredEvent::class,
            \App\Domain\Event\CondominiumRenamedEvent::eventType() => \App\Domain\Event\CondominiumRenamedEvent::class,
            \App\Domain\Event\CondominiumAddressChangedEvent::eventType() => \App\Domain\Event\CondominiumAddressChangedEvent::class,
            \App\Domain\Event\UnitCreatedEvent::eventType() => \App\Domain\Event\UnitCreatedEvent::class,
            \App\Domain\Event\ExpenseRecordedEvent::eventType() => \App\Domain\Event\ExpenseRecordedEvent::class,
            \App\Domain\Event\ExpenseCategoryCreatedEvent::eventType() => \App\Domain\Event\ExpenseCategoryCreatedEvent::class,
        ]);
        $eventStore = new PostgresEventStore(self::$pdo, $eventSerializer, $this->eventDeserializer);

        // Setup Projectors
        $condoProjector = new CondominiumProjector(self::$pdo);
        $unitProjector = new UnitProjector(self::$pdo); // For createUnit endpoint
        $expenseProjector = new ExpenseProjector(self::$pdo); // For recordExpense endpoint
        // ExpenseCategoryProjector would be needed if we test that category creation reflects in read model

        $this->projectionManager = new ProjectionManager([$condoProjector, $unitProjector, $expenseProjector], new NullLogger());

        // Repositories
        $this->condoRepository = new PostgresCondominiumRepository($eventStore, $this->projectionManager);
        $this->unitRepository = new PostgresUnitRepository($eventStore, $this->projectionManager);
        $this->expenseRepository = new PostgresExpenseRepository($eventStore, $this->projectionManager);
        $this->expenseCategoryRepository = new PostgresExpenseCategoryRepository($eventStore, $this->projectionManager);


        // Command Handlers
        $registerCondoHandler = new RegisterCondominiumCommandHandler($this->condoRepository);
        $createUnitHandler = new CreateUnitCommandHandler($this->unitRepository /*, $this->condoRepository*/);
        $recordExpenseHandler = new RecordCondominiumExpenseCommandHandler(
            $this->expenseRepository,
            $this->condoRepository,
            $this->expenseCategoryRepository
        );

        // Query Handlers
        $getCondoDetailsHandler = new GetCondominiumDetailsQueryHandler(self::$pdo);
        $getUnitsInCondoHandler = new GetUnitsInCondominiumQueryHandler(self::$pdo);
        $getFinancialSummaryHandler = new GetCondominiumFinancialSummaryQueryHandler(self::$pdo);

        $this->controller = new CondominiumController(
            $registerCondoHandler,
            $createUnitHandler,
            $getCondoDetailsHandler,
            $getUnitsInCondoHandler,
            $recordExpenseHandler,
            getFinancialSummaryHandler,
            new NullLogger()
        );
    }

    protected function tearDown(): void
    {
        $this->rollback();
    }

    public static function tearDownAfterClass(): void
    {
        // Optionally, clean up the database or close connection if managed per class
        // self::getPDO()->exec('DROP TABLE IF EXISTS events, condominiums, units, expenses, ...');
        self::$pdo = null;
    }

    private function preInsertExpenseCategory(CondominiumId $condoId, ExpenseCategoryId $categoryId, string $name): void
    {
        // Directly insert or use repository if it's simpler and won't interfere with event store test focus.
        // Using repository to ensure its event is also handled if necessary (though for read model tests, direct insert is fine).
        $category = ExpenseCategory::createNew($categoryId, $condoId, $name);
        $this->expenseCategoryRepository->save($category); // This will also project via ProjectionManager if projector exists
    }


    public function testRegisterCondominiumAndGetDetails(): void
    {
        [$responseData, $statusCode] = $this->controller->registerCondominium(
            "Sunset Hills", "123 Main St", "Metropolis", "12345", "USA"
        );
        $this->assertEquals(201, $statusCode);
        $this->assertArrayHasKey('condominiumId', $responseData);
        $condoId = $responseData['condominiumId'];

        [$dto, $statusCode] = $this->controller->getCondominiumDetails($condoId);
        $this->assertEquals(200, $statusCode);
        $this->assertInstanceOf(CondominiumDetailsDTO::class, $dto);
        $this->assertEquals("Sunset Hills", $dto->name);
    }

    public function testRecordExpenseAndGetFinancialSummary(): void
    {
        // 1. Register a condominium
        [$condoResponse, ] = $this->controller->registerCondominium("Test Condo Finance", "1 Test St", "Testville", "10000", "TCY");
        $condoId = $condoResponse['condominiumId'];
        $condoIdVO = new CondominiumId($condoId);

        // 2. Pre-insert an Expense Category for this condo
        $categoryId = ExpenseCategoryId::generate();
        $this->preInsertExpenseCategory($condoIdVO, $categoryId, "Maintenance");


        // 3. Record an expense
        [$expenseResponse, $expenseStatusCode] = $this->controller->recordExpense(
            $condoId,
            $categoryId->toString(),
            "Elevator Repair",
            50000, // 500.00
            "USD",
            "2024-01-15"
        );
        $this->assertEquals(201, $expenseStatusCode);
        $this->assertArrayHasKey('expenseId', $expenseResponse);

        // 4. Get financial summary
        [$summaryDto, $summaryStatusCode] = $this->controller->getFinancialSummary($condoId);
        $this->assertEquals(200, $summaryStatusCode);
        $this->assertInstanceOf(CondominiumFinancialSummaryDTO::class, $summaryDto);
        $this->assertEquals($condoId, $summaryDto->condominiumId);
        $this->assertEquals(0, $summaryDto->totalIncomeCents); // No income recorded yet
        $this->assertEquals(50000, $summaryDto->totalExpensesCents);
        $this->assertEquals(-50000, $summaryDto->netBalanceCents);
        $this->assertEquals("USD", $summaryDto->currencyCode); // Currency from expense
    }
}

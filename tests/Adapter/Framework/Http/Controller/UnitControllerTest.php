<?php

declare(strict_types=1);

namespace Tests\Adapter\Framework\Http\Controller;

use App\Adapter\Framework\Http\Controller\UnitController;
use App\Application\Command\AssignOwnerToUnitCommandHandler;
use App\Application\Command\IssueFeeToUnitCommandHandler;
use App\Application\Command\ReceivePaymentFromUnitCommandHandler;
use App\Application\Query\GetUnitStatementQueryHandler;
use App\Application\DTO\UnitStatementDTO; // For asserting response
use App\Domain\Entity\Condominium; // For setup
use App\Domain\Entity\FeeItem; // For setup
use App\Domain\Entity\Owner; // For setup
use App\Domain\Entity\Unit; // For setup
use App\Domain\ValueObject\Address;
use App\Domain\ValueObject\CondominiumId;
use App\Domain\ValueObject\Currency;
use App\Domain\ValueObject\FeeItemId;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\OwnerId;
use App\Domain\ValueObject\UnitId;
use App\Infrastructure\EventSourcing\BasicEventDeserializer;
use App\Infrastructure\EventSourcing\BasicEventSerializer;
use App\Infrastructure\Persistence\Postgres\PostgresCondominiumRepository;
use App\Infrastructure\Persistence\Postgres\PostgresEventStore;
use App\Infrastructure\Persistence\Postgres\PostgresFeeItemRepository;
use App\Infrastructure\Persistence\Postgres\PostgresOwnerRepository;
use App\Infrastructure\Persistence\Postgres\PostgresUnitLedgerAccountRepository;
use App\Infrastructure\Persistence\Postgres\PostgresUnitRepository;
use App\Infrastructure\Projection\CondominiumProjector; // For setup
use App\Infrastructure\Projection\FeeItemProjector; // For setup, if exists, or direct insert
use App\Infrastructure\Projection\FeeProjector;
use App\Infrastructure\Projection\OwnerProjector; // For setup
use App\Infrastructure\Projection\PaymentProjector;
use App\Infrastructure\Projection\ProjectionManager;
use App\Infrastructure\Projection\UnitLedgerAccountProjector;
use App\Infrastructure\Projection\UnitProjector;
use PDO;
use Psr\Log\NullLogger;
use PHPUnit\Framework\TestCase;
use Tests\Integration\DatabaseTestCaseHelper;
use DateTimeImmutable;

final class UnitControllerTest extends TestCase
{
    use DatabaseTestCaseHelper;

    // private static PDO $pdo; // Removed, rely on trait's property
    private UnitController $controller;
    private BasicEventDeserializer $eventDeserializer;
    private ProjectionManager $projectionManager;

    // Repositories for setup and handlers
    private PostgresCondominiumRepository $condoRepository;
    private PostgresUnitRepository $unitRepository;
    private PostgresOwnerRepository $ownerRepository;
    private PostgresFeeItemRepository $feeItemRepository;
    private PostgresUnitLedgerAccountRepository $unitLedgerAccountRepository;

    public static function setUpBeforeClass(): void
    {
        self::setUpBeforeClassNeedsDb();
        self::$pdo = self::getPDO();
    }

    protected function setUp(): void
    {
        $this->startTransaction();

        $eventSerializer = new BasicEventSerializer();
        $this->eventDeserializer = new BasicEventDeserializer([
            // Core Aggregates
            \App\Domain\Event\CondominiumRegisteredEvent::eventType() => \App\Domain\Event\CondominiumRegisteredEvent::class,
            \App\Domain\Event\UnitCreatedEvent::eventType() => \App\Domain\Event\UnitCreatedEvent::class,
            \App\Domain\Event\OwnerCreatedEvent::eventType() => \App\Domain\Event\OwnerCreatedEvent::class,
            \App\Domain\Event\OwnerAssignedToUnitEvent::eventType() => \App\Domain\Event\OwnerAssignedToUnitEvent::class,
            // Financial Aggregates
            \App\Domain\Event\FeeItemCreatedEvent::eventType() => \App\Domain\Event\FeeItemCreatedEvent::class,
            \App\Domain\Event\UnitLedgerAccountCreatedEvent::eventType() => \App\Domain\Event\UnitLedgerAccountCreatedEvent::class,
            \App\Domain\Event\FeeAppliedToUnitLedgerEvent::eventType() => \App\Domain\Event\FeeAppliedToUnitLedgerEvent::class,
            \App\Domain\Event\PaymentReceivedOnUnitLedgerEvent::eventType() => \App\Domain\Event\PaymentReceivedOnUnitLedgerEvent::class,
        ]);
        $eventStore = new PostgresEventStore(self::$pdo, $eventSerializer, $this->eventDeserializer);

        // Projectors
        $this->projectionManager = new ProjectionManager([
            new CondominiumProjector(self::$pdo),
            new UnitProjector(self::$pdo),
            new OwnerProjector(self::$pdo),
            new UnitLedgerAccountProjector(self::$pdo),
            new FeeProjector(self::$pdo),
            new PaymentProjector(self::$pdo),
            // new FeeItemProjector(self::$pdo), // Assuming FeeItem read model table and projector if needed
        ], new NullLogger());

        // Repositories
        $this->condoRepository = new PostgresCondominiumRepository($eventStore, $this->projectionManager);
        $this->unitRepository = new PostgresUnitRepository($eventStore, $this->projectionManager);
        $this->ownerRepository = new PostgresOwnerRepository($eventStore, $this->projectionManager);
        $this->feeItemRepository = new PostgresFeeItemRepository($eventStore, $this->projectionManager);
        $this->unitLedgerAccountRepository = new PostgresUnitLedgerAccountRepository($eventStore, $this->projectionManager);

        // Command Handlers
        $assignOwnerHandler = new AssignOwnerToUnitCommandHandler($this->unitRepository, $this->ownerRepository);
        $issueFeeHandler = new IssueFeeToUnitCommandHandler($this->unitLedgerAccountRepository, $this->unitRepository, $this->feeItemRepository);
        $receivePaymentHandler = new ReceivePaymentFromUnitCommandHandler($this->unitLedgerAccountRepository, $this->unitRepository);

        // Query Handlers
        $getUnitStatementHandler = new GetUnitStatementQueryHandler(self::$pdo);

        $this->controller = new UnitController(
            $assignOwnerHandler,
            $issueFeeHandler,
            $receivePaymentHandler,
            getUnitStatementHandler,
            new NullLogger()
        );
    }

    protected function tearDown(): void
    {
        $this->rollback();
    }

    public static function tearDownAfterClass(): void
    {
        self::$pdo = null;
    }

    private function setupCondominiumUnitAndFeeItem(): array
    {
        $condoId = CondominiumId::generate();
        $condo = Condominium::createNew($condoId, "Test Condo", new Address("1 St", "City", "PC", "CY"));
        $this->condoRepository->save($condo);

        $unitId = UnitId::generate();
        $unit = Unit::createNew($unitId, $condoId, "Unit 101");
        $this->unitRepository->save($unit);

        $feeItemId = FeeItemId::generate();
        $feeItem = FeeItem::createNew($feeItemId, $condoId, "Monthly Condo Fee", new Money(10000, new Currency("USD")));
        $this->feeItemRepository->save($feeItem);

        // Create initial ledger account for the unit by issuing a zero-value fee (or handle creation differently)
        // For test simplicity, we'll let IssueFeeToUnitCommandHandler create it if not exists
        // Or we can create it directly here.
        $ledger = UnitLedgerAccount::createNew($unitId, new Money(0, new Currency("USD")));
        $this->unitLedgerAccountRepository->save($ledger);


        return ['unitId' => $unitId, 'feeItemId' => $feeItemId, 'condoId' => $condoId];
    }

    public function testIssueFeeAndGetStatement(): void
    {
        $ids = $this->setupCondominiumUnitAndFeeItem();
        $unitIdStr = $ids['unitId']->toString();
        $feeItemIdStr = $ids['feeItemId']->toString();

        // 1. Issue a Fee
        [$feeResponse, $feeStatusCode] = $this->controller->issueFee(
            $unitIdStr,
            $feeItemIdStr,
            10000, // 100.00 USD
            "USD",
            "2024-03-01",
            "Monthly Condo Fee for March"
        );
        $this->assertEquals(201, $feeStatusCode);
        $this->assertEquals(['message' => 'Fee issued to unit successfully'], $feeResponse);

        // 2. Receive a Payment
        [$paymentResponse, $paymentStatusCode] = $this->controller->receivePayment(
            $unitIdStr,
            7500, // 75.00 USD
            "USD",
            "2024-03-05",
            "Online Transfer"
        );
        $this->assertEquals(201, $paymentStatusCode);
        $this->assertEquals(['message' => 'Payment received successfully'], $paymentResponse);

        // 3. Get Unit Statement
        [$statementDto, $statementStatusCode] = $this->controller->getStatement($unitIdStr);
        $this->assertEquals(200, $statementStatusCode);
        $this->assertInstanceOf(UnitStatementDTO::class, $statementDto);

        // Balance: 0 (initial) + 10000 (fee) - 7500 (payment) = 2500
        $this->assertEquals($unitIdStr, $statementDto->unitId);
        $this->assertEquals(2500, $statementDto->currentBalance->balanceAmountCents);
        $this->assertEquals("USD", $statementDto->currentBalance->balanceCurrencyCode);

        $this->assertCount(1, $statementDto->feesIssued);
        $this->assertEquals($feeItemIdStr, $statementDto->feesIssued[0]->feeItemId);
        $this->assertEquals(10000, $statementDto->feesIssued[0]->amountCents);

        $this->assertCount(1, $statementDto->paymentsReceived);
        $this->assertEquals(7500, $statementDto->paymentsReceived[0]->amountCents);
    }
}

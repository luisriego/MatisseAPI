<?php

declare(strict_types=1);

namespace App\Infrastructure;

// Application Commands & Handlers
use App\Application\Command\AssignOwnerToUnitCommand;
use App\Application\Command\AssignOwnerToUnitCommandHandler;
use App\Application\Command\CreateOwnerCommand;
use App\Application\Command\CreateOwnerCommandHandler;
use App\Application\Command\CreateUnitCommand;
use App\Application\Command\CreateUnitCommandHandler;
use App\Application\Command\IssueFeeToUnitCommand;
use App\Application\Command\IssueFeeToUnitCommandHandler;
use App\Application\Command\ReceivePaymentFromUnitCommand;
use App\Application\Command\ReceivePaymentFromUnitCommandHandler;
use App\Application\Command\RecordCondominiumExpenseCommand;
use App\Application\Command\RecordCondominiumExpenseCommandHandler;
use App\Application\Command\RegisterCondominiumCommand;
use App\Application\Command\RegisterCondominiumCommandHandler;

// Application Queries & Handlers
use App\Application\Query\GetCondominiumDetailsQuery;
use App\Application\Query\GetCondominiumDetailsQueryHandler;
use App\Application\Query\GetCondominiumFinancialSummaryQuery;
use App\Application\Query\GetCondominiumFinancialSummaryQueryHandler;
use App\Application\Query\GetUnitsInCondominiumQuery;
use App\Application\Query\GetUnitsInCondominiumQueryHandler;
use App\Application\Query\GetUnitStatementQuery;
use App\Application\Query\GetUnitStatementQueryHandler;

// Application Ports (Interfaces for Repositories)
use App\Application\Port\Out\CondominiumRepositoryInterface;
use App\Application\Port\Out\EventStoreInterface;
use App\Application\Port\Out\ExpenseCategoryRepositoryInterface;
use App\Application\Port\Out\ExpenseRepositoryInterface;
use App\Application\Port\Out\FeeItemRepositoryInterface;
use App\Application\Port\Out\OwnerRepositoryInterface;
use App\Application\Port\Out\UnitLedgerAccountRepositoryInterface;
use App\Application\Port\Out\UnitRepositoryInterface;

// Infrastructure Components
use App\Infrastructure\EventSourcing\BasicEventDeserializer;
use App\Infrastructure\EventSourcing\BasicEventSerializer;
use App\Infrastructure\EventSourcing\EventDeserializerInterface;
use App\Infrastructure\EventSourcing\EventMap;
use App\Infrastructure\EventSourcing\EventSerializerInterface;
use App\Infrastructure\Persistence\Postgres\PostgresCondominiumRepository;
use App\Infrastructure\Persistence\Postgres\PostgresEventStore;
use App\Infrastructure\Persistence\Postgres\PostgresExpenseCategoryRepository;
use App\Infrastructure\Persistence\Postgres\PostgresExpenseRepository;
use App\Infrastructure\Persistence\Postgres\PostgresFeeItemRepository;
use App\Infrastructure\Persistence\Postgres\PostgresOwnerRepository;
use App\Infrastructure\Persistence\Postgres\PostgresUnitLedgerAccountRepository;
use App\Infrastructure\Persistence\Postgres\PostgresUnitRepository;
use App\Infrastructure\Projection\CondominiumProjector;
use App\Infrastructure\Projection\ExpenseProjector;
use App\Infrastructure\Projection\FeeProjector;
use App\Infrastructure\Projection\OwnerProjector;
use App\Infrastructure\Projection\PaymentProjector;
use App\Infrastructure\Projection\ProjectionManager;
use App\Infrastructure\Projection\UnitLedgerAccountProjector;
use App\Infrastructure\Projection\UnitProjector;

// Adapters (Controllers)
use App\Adapter\Framework\Http\Controller\CondominiumController;
use App\Adapter\Framework\Http\Controller\OwnerController;
use App\Adapter\Framework\Http\Controller\UnitController;

// PSR Logger
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class ServiceFactory
{
    private ?\PDO $pdo = null;
    private ?EventStoreInterface $eventStore = null;
    private ?ProjectionManager $projectionManager = null;
    private array $repositories = [];
    private array $projectorsCache = []; // Cache for projector instances
    private ?EventSerializerInterface $eventSerializer = null;
    private ?EventDeserializerInterface $eventDeserializer = null;
    private ?LoggerInterface $logger = null;

    public function __construct(
        private string $dbHost,
        private string $dbName,
        private string $dbUser,
        private string $dbPass,
        private string $dbPort = '5432'
    ) {}

    public function getPDO(): \PDO
    {
        if ($this->pdo === null) {
            $dsn = "pgsql:host={$this->dbHost};port={$this->dbPort};dbname={$this->dbName}";
            try {
                 $this->pdo = new \PDO($dsn, $this->dbUser, $this->dbPass, [
                    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                    \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                ]);
            } catch (\PDOException $e) {
                // Attempt to connect without specifying dbname to create it (simple version)
                $dsnSys = "pgsql:host={$this->dbHost};port={$this->dbPort}";
                $pdoSys = new \PDO($dsnSys, $this->dbUser, $this->dbPass);
                $pdoSys->exec("CREATE DATABASE \"{$this->dbName}\""); // Use quotes for db name
                // Now connect to the created DB
                 $this->pdo = new \PDO($dsn, $this->dbUser, $this->dbPass, [
                    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                    \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                ]);
            }
        }
        return $this->pdo;
    }

    public function getLogger(): LoggerInterface
    {
        if ($this->logger === null) {
            // In a real app, configure a real logger (e.g., Monolog)
            $this->logger = new NullLogger();
        }
        return $this->logger;
    }

    public function getEventSerializer(): EventSerializerInterface
    {
        if ($this->eventSerializer === null) {
            $this->eventSerializer = new BasicEventSerializer();
        }
        return $this->eventSerializer;
    }

    public function getEventDeserializer(): EventDeserializerInterface
    {
        if ($this->eventDeserializer === null) {
            $this->eventDeserializer = new BasicEventDeserializer(EventMap::getMap());
        }
        return $this->eventDeserializer;
    }

    public function getEventStore(): EventStoreInterface
    {
        if ($this->eventStore === null) {
            $this->eventStore = new PostgresEventStore(
                $this->getPDO(),
                $this->getEventSerializer(),
                $this->getEventDeserializer()
            );
        }
        return $this->eventStore;
    }

    private function getProjector(string $class): object
    {
        if (!isset($this->projectorsCache[$class])) {
            $this->projectorsCache[$class] = new $class($this->getPDO());
        }
        return $this->projectorsCache[$class];
    }

    public function getProjectionManager(): ProjectionManager
    {
        if ($this->projectionManager === null) {
            $projectors = [
                $this->getProjector(CondominiumProjector::class),
                $this->getProjector(OwnerProjector::class),
                $this->getProjector(UnitProjector::class),
                $this->getProjector(UnitLedgerAccountProjector::class),
                $this->getProjector(ExpenseProjector::class),
                $this->getProjector(FeeProjector::class),
                $this->getProjector(PaymentProjector::class),
                // new ExpenseCategoryProjector($this->getPDO()), // Assuming it exists
                // new FeeItemProjector($this->getPDO()),       // Assuming it exists
            ];
            $this->projectionManager = new ProjectionManager($projectors, $this->getLogger());
        }
        return $this->projectionManager;
    }

    private function getRepository(string $interface, string $class): object
    {
        if (!isset($this->repositories[$class])) {
            $this->repositories[$class] = new $class($this->getEventStore(), $this->getProjectionManager());
        }
        return $this->repositories[$class];
    }

    public function getCondominiumRepository(): CondominiumRepositoryInterface
    {
        return $this->getRepository(CondominiumRepositoryInterface::class, PostgresCondominiumRepository::class);
    }
    public function getUnitRepository(): UnitRepositoryInterface
    {
        return $this->getRepository(UnitRepositoryInterface::class, PostgresUnitRepository::class);
    }
    public function getOwnerRepository(): OwnerRepositoryInterface
    {
        return $this->getRepository(OwnerRepositoryInterface::class, PostgresOwnerRepository::class);
    }
    public function getExpenseRepository(): ExpenseRepositoryInterface
    {
        return $this->getRepository(ExpenseRepositoryInterface::class, PostgresExpenseRepository::class);
    }
    public function getUnitLedgerAccountRepository(): UnitLedgerAccountRepositoryInterface
    {
        return $this->getRepository(UnitLedgerAccountRepositoryInterface::class, PostgresUnitLedgerAccountRepository::class);
    }
    public function getFeeItemRepository(): FeeItemRepositoryInterface
    {
        return $this->getRepository(FeeItemRepositoryInterface::class, PostgresFeeItemRepository::class);
    }
    public function getExpenseCategoryRepository(): ExpenseCategoryRepositoryInterface
    {
        return $this->getRepository(ExpenseCategoryRepositoryInterface::class, PostgresExpenseCategoryRepository::class);
    }

    // Command Handlers
    public function getRegisterCondominiumCommandHandler(): RegisterCondominiumCommandHandler
    {
        return new RegisterCondominiumCommandHandler($this->getCondominiumRepository());
    }
    public function getCreateUnitCommandHandler(): CreateUnitCommandHandler
    {
        return new CreateUnitCommandHandler($this->getUnitRepository() /*, $this->getCondominiumRepository()*/);
    }
    public function getCreateOwnerCommandHandler(): CreateOwnerCommandHandler
    {
        return new CreateOwnerCommandHandler($this->getOwnerRepository());
    }
    public function getAssignOwnerToUnitCommandHandler(): AssignOwnerToUnitCommandHandler
    {
        return new AssignOwnerToUnitCommandHandler($this->getUnitRepository(), $this->getOwnerRepository());
    }
    public function getRecordCondominiumExpenseCommandHandler(): RecordCondominiumExpenseCommandHandler
    {
        return new RecordCondominiumExpenseCommandHandler(
            $this->getExpenseRepository(),
            $this->getCondominiumRepository(),
            $this->getExpenseCategoryRepository()
        );
    }
    public function getIssueFeeToUnitCommandHandler(): IssueFeeToUnitCommandHandler
    {
        return new IssueFeeToUnitCommandHandler(
            $this->getUnitLedgerAccountRepository(),
            $this->getUnitRepository(),
            $this->getFeeItemRepository()
        );
    }
    public function getReceivePaymentFromUnitCommandHandler(): ReceivePaymentFromUnitCommandHandler
    {
        return new ReceivePaymentFromUnitCommandHandler(
            $this->getUnitLedgerAccountRepository(),
            $this->getUnitRepository()
        );
    }

    // Query Handlers
    public function getGetCondominiumDetailsQueryHandler(): GetCondominiumDetailsQueryHandler
    {
        return new GetCondominiumDetailsQueryHandler($this->getPDO());
    }
    public function getGetUnitsInCondominiumQueryHandler(): GetUnitsInCondominiumQueryHandler
    {
        return new GetUnitsInCondominiumQueryHandler($this->getPDO());
    }
    public function getGetUnitStatementQueryHandler(): GetUnitStatementQueryHandler
    {
        return new GetUnitStatementQueryHandler($this->getPDO());
    }
    public function getGetCondominiumFinancialSummaryQueryHandler(): GetCondominiumFinancialSummaryQueryHandler
    {
        return new GetCondominiumFinancialSummaryQueryHandler($this->getPDO());
    }

    // Controllers
    public function getCondominiumController(): CondominiumController
    {
        return new CondominiumController(
            $this->getRegisterCondominiumCommandHandler(),
            $this->getCreateUnitCommandHandler(),
            $this->getGetCondominiumDetailsQueryHandler(),
            $this->getGetUnitsInCondominiumQueryHandler(),
            $this->getRecordCondominiumExpenseCommandHandler(),
            $this->getGetCondominiumFinancialSummaryQueryHandler(),
            $this->getLogger()
        );
    }
    public function getOwnerController(): OwnerController
    {
        return new OwnerController(
            $this->getCreateOwnerCommandHandler(),
            $this->getLogger()
        );
    }
    public function getUnitController(): UnitController
    {
        return new UnitController(
            $this->getAssignOwnerToUnitCommandHandler(),
            $this->getIssueFeeToUnitCommandHandler(),
            $this->getReceivePaymentFromUnitCommandHandler(),
            $this->getGetUnitStatementQueryHandler(),
            $this->getLogger()
        );
    }
}

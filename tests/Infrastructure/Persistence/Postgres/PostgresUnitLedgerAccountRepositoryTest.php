<?php

declare(strict_types=1);

namespace Tests\Infrastructure\Persistence\Postgres;

use App\Domain\Entity\UnitLedgerAccount;
use App\Domain\ValueObject\UnitId;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\Currency;
use App\Domain\ValueObject\FeeItemId; // For testing applyFee
use App\Domain\ValueObject\TransactionId; // For testing receivePayment
use App\Infrastructure\EventSourcing\BasicEventDeserializer;
use App\Infrastructure\EventSourcing\BasicEventSerializer;
use App\Infrastructure\Persistence\Postgres\PostgresEventStore;
use App\Infrastructure\Persistence\Postgres\PostgresUnitLedgerAccountRepository;
use App\Infrastructure\Projection\ProjectionManager; // Mocked
use DateTimeImmutable; // For testing event data
use PHPUnit\Framework\TestCase;
use Tests\Integration\DatabaseTestCaseHelper;

final class PostgresUnitLedgerAccountRepositoryTest extends TestCase
{
    use DatabaseTestCaseHelper;

    private PostgresEventStore $eventStore;
    private PostgresUnitLedgerAccountRepository $ledgerRepository;
    private BasicEventDeserializer $eventDeserializer;

    public static function setUpBeforeClass(): void
    {
        self::setUpBeforeClassNeedsDb();
    }

    protected function setUp(): void
    {
        $pdo = self::getPDO();
        $this->startTransaction();

        $this->eventDeserializer = new BasicEventDeserializer([
            \App\Domain\Event\UnitLedgerAccountCreatedEvent::eventType() => \App\Domain\Event\UnitLedgerAccountCreatedEvent::class,
            \App\Domain\Event\FeeAppliedToUnitLedgerEvent::eventType() => \App\Domain\Event\FeeAppliedToUnitLedgerEvent::class,
            \App\Domain\Event\PaymentReceivedOnUnitLedgerEvent::eventType() => \App\Domain\Event\PaymentReceivedOnUnitLedgerEvent::class,
        ]);
        $this->eventStore = new PostgresEventStore($pdo, new BasicEventSerializer(), $this->eventDeserializer);

        $projectionManagerMock = $this->createMock(ProjectionManager::class);
        $this->ledgerRepository = new PostgresUnitLedgerAccountRepository($this->eventStore, $projectionManagerMock);
    }

    protected function tearDown(): void
    {
        $this->rollback();
    }

    public static function tearDownAfterClass(): void
    {
        self::$pdo = null;
    }

    public function testSaveAndFindByUnitIdForNewAccount(): void
    {
        $unitId = UnitId::generate();
        $initialBalance = new Money(1000, new Currency("USD"));

        $account = UnitLedgerAccount::createNew($unitId, $initialBalance);
        $this->ledgerRepository->save($account);

        $foundAccount = $this->ledgerRepository->findByUnitId($unitId);

        $this->assertNotNull($foundAccount);
        $this->assertTrue($unitId->equals($foundAccount->getId()));
        $this->assertTrue($initialBalance->equals($foundAccount->getBalance()));
    }

    public function testSaveAndFindByUnitIdWithMultipleEvents(): void
    {
        $unitId = UnitId::generate();
        $initialBalance = new Money(0, new Currency("USD"));
        $account = UnitLedgerAccount::createNew($unitId, $initialBalance);

        $feeItemId = FeeItemId::generate();
        $feeAmount = new Money(5000, new Currency("USD"));
        $dueDate = new DateTimeImmutable("2024-12-01");
        $account->applyFee($feeItemId, $feeAmount, $dueDate, "Annual Fee");

        $paymentAmount = new Money(2000, new Currency("USD"));
        $paymentId = TransactionId::generate();
        $paymentDate = new DateTimeImmutable("2024-07-15");
        $account->receivePayment($paymentAmount, $paymentId, $paymentDate, "Bank Transfer");

        $this->ledgerRepository->save($account);

        $foundAccount = $this->ledgerRepository->findByUnitId($unitId);
        $this->assertNotNull($foundAccount);
        $this->assertTrue($unitId->equals($foundAccount->getId()));
        // Expected balance: 0 + 5000 - 2000 = 3000
        $this->assertEquals(3000, $foundAccount->getBalance()->getAmount());
    }

    public function testFindByUnitIdReturnsNullForNonExistentAccount(): void
    {
        $nonExistentUnitId = UnitId::generate();
        $foundAccount = $this->ledgerRepository->findByUnitId($nonExistentUnitId);
        $this->assertNull($foundAccount);
    }
}

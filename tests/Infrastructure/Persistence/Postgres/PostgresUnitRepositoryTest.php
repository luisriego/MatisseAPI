<?php

declare(strict_types=1);

namespace Tests\Infrastructure\Persistence\Postgres;

use App\Domain\Entity\Unit;
use App\Domain\ValueObject\CondominiumId;
use App\Domain\ValueObject\OwnerId;
use App\Domain\ValueObject\UnitId;
use App\Infrastructure\EventSourcing\BasicEventDeserializer;
use App\Infrastructure\EventSourcing\BasicEventSerializer;
use App\Infrastructure\Persistence\Postgres\PostgresEventStore;
use App\Infrastructure\Persistence\Postgres\PostgresUnitRepository;
use App\Infrastructure\Projection\ProjectionManager; // Mocked
use PHPUnit\Framework\TestCase;
use Tests\Integration\DatabaseTestCaseHelper;

final class PostgresUnitRepositoryTest extends TestCase
{
    use DatabaseTestCaseHelper;

    private PostgresEventStore $eventStore;
    private PostgresUnitRepository $unitRepository;
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
            \App\Domain\Event\UnitCreatedEvent::eventType() => \App\Domain\Event\UnitCreatedEvent::class,
            \App\Domain\Event\OwnerAssignedToUnitEvent::eventType() => \App\Domain\Event\OwnerAssignedToUnitEvent::class,
            \App\Domain\Event\OwnerRemovedFromUnitEvent::eventType() => \App\Domain\Event\OwnerRemovedFromUnitEvent::class,
        ]);
        $this->eventStore = new PostgresEventStore($pdo, new BasicEventSerializer(), $this->eventDeserializer);

        $projectionManagerMock = $this->createMock(ProjectionManager::class);
        $this->unitRepository = new PostgresUnitRepository($this->eventStore, $projectionManagerMock);
    }

    protected function tearDown(): void
    {
        $this->rollback();
    }

    public static function tearDownAfterClass(): void
    {
        self::$pdo = null;
    }

    public function testSaveAndFindById_NewUnit(): void
    {
        $unitId = UnitId::generate();
        $condoId = CondominiumId::generate();
        $identifier = "A101";

        $unit = Unit::createNew($unitId, $condoId, $identifier);
        $this->unitRepository->save($unit);

        $foundUnit = $this->unitRepository->findById($unitId);

        $this->assertNotNull($foundUnit);
        $this->assertTrue($unitId->equals($foundUnit->getId()));
        $this->assertTrue($condoId->equals($foundUnit->getCondominiumId()));
        $this->assertEquals($identifier, $foundUnit->getIdentifier());
        $this->assertNull($foundUnit->getOwnerId());
    }

    public function testSaveAndFindById_UnitWithOwnerAssignmentAndRemoval(): void
    {
        $unitId = UnitId::generate();
        $condoId = CondominiumId::generate();
        $identifier = "B202";

        // Create and save initial state
        $unit = Unit::createNew($unitId, $condoId, $identifier);
        $this->unitRepository->save($unit);

        // Assign an owner
        $retrievedUnit = $this->unitRepository->findById($unitId);
        $this->assertNotNull($retrievedUnit);
        $ownerId = OwnerId::generate();
        $retrievedUnit->assignOwner($ownerId);
        $this->unitRepository->save($retrievedUnit);

        // Check owner assignment
        $unitWithOwner = $this->unitRepository->findById($unitId);
        $this->assertNotNull($unitWithOwner);
        $this->assertNotNull($unitWithOwner->getOwnerId());
        $this->assertTrue($ownerId->equals($unitWithOwner->getOwnerId()));

        // Remove the owner
        $unitWithOwner->removeOwner();
        $this->unitRepository->save($unitWithOwner);

        // Check owner removal
        $unitWithoutOwner = $this->unitRepository->findById($unitId);
        $this->assertNotNull($unitWithoutOwner);
        $this->assertNull($unitWithoutOwner->getOwnerId());
    }

    public function testFindById_NonExistentUnit_ReturnsNull(): void
    {
        $nonExistentId = UnitId::generate();
        $foundUnit = $this->unitRepository->findById($nonExistentId);
        $this->assertNull($foundUnit);
    }
}

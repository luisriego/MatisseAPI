<?php

declare(strict_types=1);

namespace Tests\Infrastructure\Persistence\Postgres;

use App\Domain\Entity\Condominium;
use App\Domain\ValueObject\Address;
use App\Domain\ValueObject\CondominiumId;
use App\Infrastructure\EventSourcing\BasicEventDeserializer;
use App\Infrastructure\EventSourcing\BasicEventSerializer;
use App\Infrastructure\Persistence\Postgres\PostgresCondominiumRepository;
use App\Infrastructure\Persistence\Postgres\PostgresEventStore;
use App\Infrastructure\Projection\ProjectionManager; // Mocked
use PHPUnit\Framework\TestCase;
use Tests\Integration\DatabaseTestCaseHelper;

final class PostgresCondominiumRepositoryTest extends TestCase
{
    use DatabaseTestCaseHelper;

    private PostgresEventStore $eventStore;
    private PostgresCondominiumRepository $condominiumRepository;
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
            \App\Domain\Event\CondominiumRegisteredEvent::eventType() => \App\Domain\Event\CondominiumRegisteredEvent::class,
            \App\Domain\Event\CondominiumRenamedEvent::eventType() => \App\Domain\Event\CondominiumRenamedEvent::class,
            \App\Domain\Event\CondominiumAddressChangedEvent::eventType() => \App\Domain\Event\CondominiumAddressChangedEvent::class,
        ]);
        $this->eventStore = new PostgresEventStore($pdo, new BasicEventSerializer(), $this->eventDeserializer);

        $projectionManagerMock = $this->createMock(ProjectionManager::class);
        // In a real scenario, if Condominium events feed projectors that this test might interact with (e.g. read model checks),
        // then a real or more specifically mocked ProjectionManager might be needed.
        // For testing just the repository's event sourcing capability, mocking is fine.

        $this->condominiumRepository = new PostgresCondominiumRepository($this->eventStore, $projectionManagerMock);
    }

    protected function tearDown(): void
    {
        $this->rollback();
    }

    public static function tearDownAfterClass(): void
    {
        self::$pdo = null;
    }

    public function testSaveAndFindById_NewCondominium(): void
    {
        $condoId = CondominiumId::generate();
        $name = "Grand Plaza";
        $address = new Address("123 Main St", "Metroville", "12345", "USA");

        $condominium = Condominium::createNew($condoId, $name, $address);
        $this->condominiumRepository->save($condominium);

        $foundCondominium = $this->condominiumRepository->findById($condoId);

        $this->assertNotNull($foundCondominium);
        $this->assertTrue($condoId->equals($foundCondominium->getId()));
        $this->assertEquals($name, $foundCondominium->getName());
        $this->assertTrue($address->equals($foundCondominium->getAddress()));
        $this->assertEmpty($foundCondominium->getUnitIds()); // Initially no units
    }

    public function testSaveAndFindById_UpdatedCondominium(): void
    {
        $condoId = CondominiumId::generate();
        $initialName = "Old Towers";
        $initialAddress = new Address("1 Old Way", "Oldtown", "001", "UK");

        // Create and save initial state
        $condominium = Condominium::createNew($condoId, $initialName, $initialAddress);
        $this->condominiumRepository->save($condominium);

        // Modify the aggregate
        $retrievedCondo = $this->condominiumRepository->findById($condoId);
        $this->assertNotNull($retrievedCondo);

        $newName = "New Heights";
        $newAddress = new Address("2 New Ave", "Newcity", "002", "UK");

        $retrievedCondo->rename($newName);
        $retrievedCondo->changeAddress($newAddress);
        // Note: addUnit is not tested here as its eventing is more complex / not fully decided for Condo AR

        $this->condominiumRepository->save($retrievedCondo);

        // Retrieve again and check updated state
        $updatedCondo = $this->condominiumRepository->findById($condoId);
        $this->assertNotNull($updatedCondo);
        $this->assertEquals($newName, $updatedCondo->getName());
        $this->assertTrue($newAddress->equals($updatedCondo->getAddress()));
    }

    public function testFindById_NonExistentCondominium_ReturnsNull(): void
    {
        $nonExistentId = CondominiumId::generate();
        $foundCondominium = $this->condominiumRepository->findById($nonExistentId);
        $this->assertNull($foundCondominium);
    }
}

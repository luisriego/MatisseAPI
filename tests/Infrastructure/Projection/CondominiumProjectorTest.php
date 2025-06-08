<?php

declare(strict_types=1);

namespace Tests\Infrastructure\Projection;

use App\Domain\Event\CondominiumAddressChangedEvent;
use App\Domain\Event\CondominiumRegisteredEvent;
use App\Domain\Event\CondominiumRenamedEvent;
use App\Domain\ValueObject\Address;
use App\Domain\ValueObject\CondominiumId;
use App\Infrastructure\Projection\CondominiumProjector;
use PDO;
use PHPUnit\Framework\TestCase;
use Tests\Integration\DatabaseTestCaseHelper;
use DateTimeImmutable; // For event creation

final class CondominiumProjectorTest extends TestCase
{
    use DatabaseTestCaseHelper;

    private CondominiumProjector $projector;

    public static function setUpBeforeClass(): void
    {
        self::setUpBeforeClassNeedsDb(); // Ensures DB & schema are ready
    }

    protected function setUp(): void
    {
        $pdo = self::getPDO();
        $this->startTransaction(); // Each test in a transaction
        $this->projector = new CondominiumProjector($pdo);
    }

    protected function tearDown(): void
    {
        $this->rollback();
    }

    public static function tearDownAfterClass(): void
    {
        self::$pdo = null;
    }

    public function testSupportsCorrectEvents(): void
    {
        $condoId = CondominiumId::generate();
        $address = new Address("1 St", "City", "PC", "CY");

        $this->assertTrue($this->projector->supports(CondominiumRegisteredEvent::create($condoId, "Name", $address)));
        $this->assertTrue($this->projector->supports(CondominiumRenamedEvent::create($condoId, "New Name")));
        $this->assertTrue($this->projector->supports(CondominiumAddressChangedEvent::create($condoId, $address)));

        // Example of an event it should not support
        $this->assertFalse($this->projector->supports($this->createMock(\App\Domain\Event\DomainEventInterface::class)));
    }

    public function testProjectsCondominiumRegisteredEvent(): void
    {
        $condoId = CondominiumId::generate();
        $name = "Test Condominium";
        $address = new Address("123 Test St", "Testville", "12345", "TS");
        $event = CondominiumRegisteredEvent::create($condoId, $name, $address);
        // We need to simulate event occurrence time for DB check if default is not desired
        // $event->occurredOn = new DateTimeImmutable('2023-01-01 10:00:00'); // Not possible as occurredOn is private

        $this->projector->project($event);

        $stmt = self::getPDO()->prepare("SELECT * FROM condominiums WHERE id = :id");
        $stmt->execute([':id' => $condoId->toString()]);
        $row = $stmt->fetch();

        $this->assertNotFalse($row);
        $this->assertEquals($name, $row['name']);
        $this->assertEquals("123 Test St", $row['address_street']);
        $this->assertEquals("Testville", $row['address_city']);
        $this->assertEquals("12345", $row['address_postal_code']);
        $this->assertEquals("TS", $row['address_country']);
        // $this->assertStringStartsWith('2023-01-01 10:00:00', $row['created_at']); // if occurredOn was controllable
    }

    public function testProjectsCondominiumRenamedEvent(): void
    {
        // First, register a condominium to update
        $condoId = CondominiumId::generate();
        $initialEvent = CondominiumRegisteredEvent::create($condoId, "Old Name", new Address("1 St", "City", "PC", "CY"));
        $this->projector->project($initialEvent); // Project the creation

        // Now, project the rename event
        $newName = "New Condo Name";
        $renameEvent = CondominiumRenamedEvent::create($condoId, $newName);
        // $renameEvent->occurredOn = new DateTimeImmutable('2023-01-01 11:00:00');

        $this->projector->project($renameEvent);

        $stmt = self::getPDO()->prepare("SELECT name, updated_at FROM condominiums WHERE id = :id");
        $stmt->execute([':id' => $condoId->toString()]);
        $row = $stmt->fetch();

        $this->assertNotFalse($row);
        $this->assertEquals($newName, $row['name']);
        // $this->assertStringStartsWith('2023-01-01 11:00:00', $row['updated_at']);
    }

    public function testProjectsCondominiumAddressChangedEvent(): void
    {
        $condoId = CondominiumId::generate();
        $initialEvent = CondominiumRegisteredEvent::create($condoId, "Condo Name", new Address("Old St", "Old City", "OLDPC", "OCY"));
        $this->projector->project($initialEvent);

        $newAddress = new Address("456 New Ave", "Newtown", "67890", "NSY");
        $addressChangedEvent = CondominiumAddressChangedEvent::create($condoId, $newAddress);

        $this->projector->project($addressChangedEvent);

        $stmt = self::getPDO()->prepare("SELECT address_street, address_city, address_postal_code, address_country FROM condominiums WHERE id = :id");
        $stmt->execute([':id' => $condoId->toString()]);
        $row = $stmt->fetch();

        $this->assertNotFalse($row);
        $this->assertEquals("456 New Ave", $row['address_street']);
        $this->assertEquals("Newtown", $row['address_city']);
        $this->assertEquals("67890", $row['address_postal_code']);
        $this->assertEquals("NSY", $row['address_country']);
    }
}

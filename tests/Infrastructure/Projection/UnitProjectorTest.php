<?php

declare(strict_types=1);

namespace Tests\Infrastructure\Projection;

use App\Domain\Event\UnitCreatedEvent;
use App\Domain\Event\OwnerAssignedToUnitEvent;
use App\Domain\Event\OwnerRemovedFromUnitEvent;
use App\Domain\ValueObject\CondominiumId;
use App\Domain\ValueObject\OwnerId;
use App\Domain\ValueObject\UnitId;
use App\Infrastructure\Projection\UnitProjector;
use PDO;
use PHPUnit\Framework\TestCase;
use Tests\Integration\DatabaseTestCaseHelper;
use DateTimeImmutable;

final class UnitProjectorTest extends TestCase
{
    use DatabaseTestCaseHelper;

    private UnitProjector $projector;

    public static function setUpBeforeClass(): void
    {
        self::setUpBeforeClassNeedsDb();
    }

    protected function setUp(): void
    {
        $pdo = self::getPDO();
        $this->startTransaction();
        $this->projector = new UnitProjector($pdo);
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
        $unitId = UnitId::generate();
        $condoId = CondominiumId::generate();
        $ownerId = OwnerId::generate();
        $this->assertTrue($this->projector->supports(UnitCreatedEvent::create($unitId, $condoId, "101")));
        $this->assertTrue($this->projector->supports(OwnerAssignedToUnitEvent::create($unitId, $ownerId)));
        $this->assertTrue($this->projector->supports(OwnerRemovedFromUnitEvent::create($unitId, $ownerId)));
        $this->assertFalse($this->projector->supports($this->createMock(\App\Domain\Event\DomainEventInterface::class)));
    }

    public function testProjectsUnitCreatedEvent(): void
    {
        $unitId = UnitId::generate();
        $condoId = CondominiumId::generate(); // Assume this condo_id exists for FK if tested strictly
        $identifier = "APT 101";
        $event = UnitCreatedEvent::create($unitId, $condoId, $identifier);

        $this->projector->project($event);

        $stmt = self::getPDO()->prepare("SELECT * FROM units WHERE id = :id");
        $stmt->execute([':id' => $unitId->toString()]);
        $row = $stmt->fetch();

        $this->assertNotFalse($row);
        $this->assertEquals($condoId->toString(), $row['condominium_id']);
        $this->assertEquals($identifier, $row['identifier']);
        $this->assertNull($row['owner_id']);
    }

    public function testProjectsOwnerAssignedToUnitEvent(): void
    {
        $unitId = UnitId::generate();
        $condoId = CondominiumId::generate();
        // 1. Create the unit first
        $createEvent = UnitCreatedEvent::create($unitId, $condoId, "UNIT 2B");
        $this->projector->project($createEvent);

        // 2. Assign an owner
        $ownerId = OwnerId::generate(); // Assume this owner_id exists for FK
        $assignEvent = OwnerAssignedToUnitEvent::create($unitId, $ownerId);
        $this->projector->project($assignEvent);

        $stmt = self::getPDO()->prepare("SELECT owner_id FROM units WHERE id = :id");
        $stmt->execute([':id' => $unitId->toString()]);
        $row = $stmt->fetch();

        $this->assertNotFalse($row);
        $this->assertEquals($ownerId->toString(), $row['owner_id']);
    }

    public function testProjectsOwnerRemovedFromUnitEvent(): void
    {
        $unitId = UnitId::generate();
        $condoId = CondominiumId::generate();
        $ownerId = OwnerId::generate();

        // 1. Create unit
        $createEvent = UnitCreatedEvent::create($unitId, $condoId, "UNIT 3C");
        $this->projector->project($createEvent);

        // 2. Assign owner
        $assignEvent = OwnerAssignedToUnitEvent::create($unitId, $ownerId);
        $this->projector->project($assignEvent);

        // 3. Remove owner
        $removeEvent = OwnerRemovedFromUnitEvent::create($unitId, $ownerId); // previousOwnerId is in event
        $this->projector->project($removeEvent);

        $stmt = self::getPDO()->prepare("SELECT owner_id FROM units WHERE id = :id");
        $stmt->execute([':id' => $unitId->toString()]);
        $row = $stmt->fetch();

        $this->assertNotFalse($row);
        $this->assertNull($row['owner_id']);
    }
}

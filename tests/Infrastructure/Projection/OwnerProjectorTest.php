<?php

declare(strict_types=1);

namespace Tests\Infrastructure\Projection;

use App\Domain\Event\OwnerCreatedEvent;
use App\Domain\Event\OwnerContactInfoUpdatedEvent;
use App\Domain\ValueObject\OwnerId;
use App\Infrastructure\Projection\OwnerProjector;
use PDO;
use PHPUnit\Framework\TestCase;
use Tests\Integration\DatabaseTestCaseHelper;
use DateTimeImmutable;

final class OwnerProjectorTest extends TestCase
{
    use DatabaseTestCaseHelper;

    private OwnerProjector $projector;

    public static function setUpBeforeClass(): void
    {
        self::setUpBeforeClassNeedsDb();
    }

    protected function setUp(): void
    {
        $pdo = self::getPDO();
        $this->startTransaction();
        $this->projector = new OwnerProjector($pdo);
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
        $ownerId = OwnerId::generate();
        $this->assertTrue($this->projector->supports(OwnerCreatedEvent::create($ownerId, "N", "e@ma.il", "P")));
        $this->assertTrue($this->projector->supports(OwnerContactInfoUpdatedEvent::create($ownerId, "NN", "ne@ma.il", "NP")));
        $this->assertFalse($this->projector->supports($this->createMock(\App\Domain\Event\DomainEventInterface::class)));
    }

    public function testProjectsOwnerCreatedEvent(): void
    {
        $ownerId = OwnerId::generate();
        $name = "John Owner";
        $email = "john.owner@example.com";
        $phone = "555-0011";
        $event = OwnerCreatedEvent::create($ownerId, $name, $email, $phone);

        $this->projector->project($event);

        $stmt = self::getPDO()->prepare("SELECT * FROM owners WHERE id = :id");
        $stmt->execute([':id' => $ownerId->toString()]);
        $row = $stmt->fetch();

        $this->assertNotFalse($row);
        $this->assertEquals($name, $row['name']);
        $this->assertEquals($email, $row['email']);
        $this->assertEquals($phone, $row['phone_number']);
    }

    public function testProjectsOwnerContactInfoUpdatedEvent(): void
    {
        $ownerId = OwnerId::generate();
        $initialEvent = OwnerCreatedEvent::create($ownerId, "Old Name", "old@mail.com", "111");
        $this->projector->project($initialEvent);

        $newName = "New Owner Name";
        $newEmail = "new.owner@example.com";
        $newPhone = "555-0022";
        $updateEvent = OwnerContactInfoUpdatedEvent::create($ownerId, $newName, $newEmail, $newPhone);

        $this->projector->project($updateEvent);

        $stmt = self::getPDO()->prepare("SELECT name, email, phone_number FROM owners WHERE id = :id");
        $stmt->execute([':id' => $ownerId->toString()]);
        $row = $stmt->fetch();

        $this->assertNotFalse($row);
        $this->assertEquals($newName, $row['name']);
        $this->assertEquals($newEmail, $row['email']);
        $this->assertEquals($newPhone, $row['phone_number']);
    }
}

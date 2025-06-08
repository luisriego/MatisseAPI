<?php

declare(strict_types=1);

namespace Tests\Infrastructure\Persistence\Postgres;

use App\Domain\Entity\Owner;
use App\Domain\ValueObject\OwnerId;
use App\Infrastructure\EventSourcing\BasicEventDeserializer;
use App\Infrastructure\EventSourcing\BasicEventSerializer;
use App\Infrastructure\Persistence\Postgres\PostgresOwnerRepository;
use App\Infrastructure\Persistence\Postgres\PostgresEventStore;
use App\Infrastructure\Projection\ProjectionManager; // Mocked
use PHPUnit\Framework\TestCase;
use Tests\Integration\DatabaseTestCaseHelper;

final class PostgresOwnerRepositoryTest extends TestCase
{
    use DatabaseTestCaseHelper;

    private PostgresEventStore $eventStore;
    private PostgresOwnerRepository $ownerRepository;
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
            \App\Domain\Event\OwnerCreatedEvent::eventType() => \App\Domain\Event\OwnerCreatedEvent::class,
            \App\Domain\Event\OwnerContactInfoUpdatedEvent::eventType() => \App\Domain\Event\OwnerContactInfoUpdatedEvent::class,
        ]);
        $this->eventStore = new PostgresEventStore($pdo, new BasicEventSerializer(), $this->eventDeserializer);

        $projectionManagerMock = $this->createMock(ProjectionManager::class);
        $this->ownerRepository = new PostgresOwnerRepository($this->eventStore, $projectionManagerMock);
    }

    protected function tearDown(): void
    {
        $this->rollback();
    }

    public static function tearDownAfterClass(): void
    {
        self::$pdo = null;
    }

    public function testSaveAndFindById_NewOwner(): void
    {
        $ownerId = OwnerId::generate();
        $name = "John Doe";
        $email = "john.doe@example.com";
        $phone = "555-1234";

        $owner = Owner::createNew($ownerId, $name, $email, $phone);
        $this->ownerRepository->save($owner);

        $foundOwner = $this->ownerRepository->findById($ownerId);

        $this->assertNotNull($foundOwner);
        $this->assertTrue($ownerId->equals($foundOwner->getId()));
        $this->assertEquals($name, $foundOwner->getName());
        $this->assertEquals($email, $foundOwner->getEmail());
        $this->assertEquals($phone, $foundOwner->getPhoneNumber());
    }

    public function testSaveAndFindById_UpdatedOwner(): void
    {
        $ownerId = OwnerId::generate();
        $initialName = "Jane Smith";
        $initialEmail = "jane.smith@example.com";
        $initialPhone = "555-5678";

        $owner = Owner::createNew($ownerId, $initialName, $initialEmail, $initialPhone);
        $this->ownerRepository->save($owner);

        $retrievedOwner = $this->ownerRepository->findById($ownerId);
        $this->assertNotNull($retrievedOwner);

        $newName = "Jane Doe"; // Changed name
        $newEmail = "jane.doe@newdomain.com"; // Changed email
        $newPhone = "555-8765"; // Changed phone

        $retrievedOwner->updateContactInfo($newName, $newEmail, $newPhone);
        $this->ownerRepository->save($retrievedOwner);

        $updatedOwner = $this->ownerRepository->findById($ownerId);
        $this->assertNotNull($updatedOwner);
        $this->assertEquals($newName, $updatedOwner->getName());
        $this->assertEquals($newEmail, $updatedOwner->getEmail());
        $this->assertEquals($newPhone, $updatedOwner->getPhoneNumber());
    }

    public function testFindById_NonExistentOwner_ReturnsNull(): void
    {
        $nonExistentId = OwnerId::generate();
        $foundOwner = $this->ownerRepository->findById($nonExistentId);
        $this->assertNull($foundOwner);
    }
}

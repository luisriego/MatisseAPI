<?php

declare(strict_types=1);

namespace Tests\Infrastructure\Persistence\Postgres;

use App\Domain\Event\AccountCreated; // Example event
use App\Domain\Event\MoneyDeposited; // Example event
use App\Domain\ValueObject\AccountId;
use App\Domain\ValueObject\Currency;
use App\Domain\ValueObject\CustomerId;
use App\Domain\ValueObject\Money;
use App\Infrastructure\EventSourcing\BasicEventDeserializer;
use App\Infrastructure\EventSourcing\BasicEventSerializer;
use App\Infrastructure\Persistence\Postgres\PostgresEventStore;
use PDO;
use PHPUnit\Framework\TestCase;
use Tests\Integration\DatabaseTestCaseHelper; // The helper trait
use Ramsey\Uuid\Uuid; // For generating event IDs if needed

final class PostgresEventStoreTest extends TestCase
{
    use DatabaseTestCaseHelper;

    private PostgresEventStore $eventStore;
    private BasicEventSerializer $serializer;
    private BasicEventDeserializer $deserializer;

    public static function setUpBeforeClass(): void
    {
        // Ensures DB exists and schema is applied if tables are missing
        self::setUpBeforeClassNeedsDb();
    }

    protected function setUp(): void
    {
        $pdo = self::getPDO();
        $this->startTransaction(); // Each test runs in a transaction

        $this->serializer = new BasicEventSerializer();

        // Event map for deserializer - crucial for these tests
        // It should include all event types that will be stored and retrieved.
        $eventMap = [
            AccountCreated::eventType() => AccountCreated::class,
            MoneyDeposited::eventType() => MoneyDeposited::class,
            // Add other event types here as needed for testing
        ];
        $this->deserializer = new BasicEventDeserializer($eventMap);

        $this->eventStore = new PostgresEventStore($pdo, $this->serializer, $this->deserializer);

        // Truncate events table before each test if not relying solely on transactions,
        // or if previous tests might have committed due to errors.
        // self::truncateTables(['events']);
        // For now, relying on transaction rollback.
    }

    protected function tearDown(): void
    {
        $this->rollback();
    }

    public static function tearDownAfterClass(): void
    {
        self::$pdo = null; // Close connection
    }

    public function testAppendAndGetEventsForAggregate(): void
    {
        $accountId = AccountId::generate();
        $customerId = CustomerId::generate();

        $event1 = AccountCreated::create(
            $accountId,
            $customerId,
            new Money(1000, new Currency('USD'))
        );
        // Manually setting occurredOn and eventId for exact comparison can be tricky if they are auto-generated
        // For this test, we'll rely on comparing the meaningful payload and types.
        // To test exact occurredOn, you might need to inject a clock or pass occurredOn to create().

        $event2 = MoneyDeposited::create(
            $accountId,
            new Money(500, new Currency('USD')),
            new Money(1500, new Currency('USD')) // new balance
        );

        $this->eventStore->append($event1, $event2);

        $retrievedEvents = $this->eventStore->getEventsForAggregate($accountId->toString());

        $this->assertCount(2, $retrievedEvents);

        // Event 1: AccountCreated
        $this->assertInstanceOf(AccountCreated::class, $retrievedEvents[0]);
        /** @var AccountCreated $retrievedEvent1 */
        $retrievedEvent1 = $retrievedEvents[0];
        $this->assertEquals($event1->getEventId(), $retrievedEvent1->getEventId());
        $this->assertEquals($accountId->toString(), $retrievedEvent1->getAggregateId());
        $this->assertEquals('Account', $retrievedEvent1->getAggregateType());
        $this->assertEquals(AccountCreated::eventType(), $retrievedEvent1::eventType());
        $this->assertTrue($customerId->equals($retrievedEvent1->getCustomerId()));
        $this->assertEquals(1000, $retrievedEvent1->getInitialBalance()->getAmount());

        // Event 2: MoneyDeposited
        $this->assertInstanceOf(MoneyDeposited::class, $retrievedEvents[1]);
        /** @var MoneyDeposited $retrievedEvent2 */
        $retrievedEvent2 = $retrievedEvents[1];
        $this->assertEquals($event2->getEventId(), $retrievedEvent2->getEventId());
        $this->assertEquals($accountId->toString(), $retrievedEvent2->getAggregateId());
        $this->assertEquals('Account', $retrievedEvent2->getAggregateType());
        $this->assertEquals(MoneyDeposited::eventType(), $retrievedEvent2::eventType());
        $this->assertEquals(500, $retrievedEvent2->getAmountDeposited()->getAmount());
        $this->assertEquals(1500, $retrievedEvent2->getNewBalance()->getAmount());

        // Check versions (PostgresEventStore assigns these)
        // This requires events to have a getVersion() method, or for version to be part of fromPayload,
        // or for the event store to return enriched event data (not just DomainEventInterface).
        // For now, the DDL has a version, and PostgresEventStore uses it.
        // The DomainEventInterface doesn't have getVersion(). So we can't directly assert it here
        // unless we query DB directly or enhance event/interface.
        // We can check they are ordered correctly (implicitly done by getEventsForAggregate).
    }

    public function testAppendIncrementsVersionCorrectly(): void
    {
        $accountId = AccountId::generate();
        $event1 = AccountCreated::create($accountId, CustomerId::generate(), new Money(100, new Currency('USD')));
        $this->eventStore->append($event1);

        $event2 = MoneyDeposited::create($accountId, new Money(50, new Currency('USD')), new Money(150, new Currency('USD')));
        $this->eventStore->append($event2);

        $event3 = MoneyDeposited::create($accountId, new Money(25, new Currency('USD')), new Money(175, new Currency('USD')));
        $this->eventStore->append($event3);

        // Query DB directly to check versions
        $stmt = self::getPDO()->prepare("SELECT event_type, version FROM events WHERE aggregate_id = :aggregate_id ORDER BY version ASC");
        $stmt->execute([':aggregate_id' => $accountId->toString()]);
        $rows = $stmt->fetchAll();

        $this->assertCount(3, $rows);
        $this->assertEquals(AccountCreated::eventType(), $rows[0]['event_type']);
        $this->assertEquals(1, (int)$rows[0]['version']);
        $this->assertEquals(MoneyDeposited::eventType(), $rows[1]['event_type']);
        $this->assertEquals(2, (int)$rows[1]['version']);
        $this->assertEquals(MoneyDeposited::eventType(), $rows[2]['event_type']);
        $this->assertEquals(3, (int)$rows[2]['version']);
    }

    public function testAppendMultipleEventsForSameAggregateInOneCall(): void
    {
        $accountId = AccountId::generate();
        $event1 = AccountCreated::create($accountId, CustomerId::generate(), new Money(100, new Currency('USD')));
        $event2 = MoneyDeposited::create($accountId, new Money(50, new Currency('USD')), new Money(150, new Currency('USD')));

        $this->eventStore->append($event1, $event2); // Append both in one call

        $stmt = self::getPDO()->prepare("SELECT event_type, version FROM events WHERE aggregate_id = :aggregate_id ORDER BY version ASC");
        $stmt->execute([':aggregate_id' => $accountId->toString()]);
        $rows = $stmt->fetchAll();

        $this->assertCount(2, $rows);
        $this->assertEquals(AccountCreated::eventType(), $rows[0]['event_type']);
        $this->assertEquals(1, (int)$rows[0]['version']);
        $this->assertEquals(MoneyDeposited::eventType(), $rows[1]['event_type']);
        $this->assertEquals(2, (int)$rows[1]['version']);
    }


    public function testGetEventsForNonExistentAggregateReturnsEmptyArray(): void
    {
        $nonExistentAggregateId = Uuid::uuid4()->toString();
        $events = $this->eventStore->getEventsForAggregate($nonExistentAggregateId);
        $this->assertIsArray($events);
        $this->assertEmpty($events);
    }
}

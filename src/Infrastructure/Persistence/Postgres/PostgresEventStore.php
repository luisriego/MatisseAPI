<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Postgres;

use App\Application\Port\Out\EventStoreInterface;
use App\Domain\Event\DomainEventInterface;
use App\Infrastructure\EventSourcing\EventDeserializerInterface;
use App\Infrastructure\EventSourcing\EventSerializerInterface;
use PDO;
use PDOException;
use PDOStatement; // For type hinting execute result
use DateTimeImmutable;
use DateTimeZone;

final class PostgresEventStore implements EventStoreInterface
{
    private PDO $pdo;
    private EventSerializerInterface $serializer;
    private EventDeserializerInterface $deserializer;

    public function __construct(
        PDO $pdo,
        EventSerializerInterface $serializer,
        EventDeserializerInterface $deserializer
    ) {
        $this->pdo = $pdo;
        $this->serializer = $serializer;
        $this->deserializer = $deserializer;
    }

    public function append(DomainEventInterface ...$domainEvents): void
    {
        if (empty($domainEvents)) {
            return;
        }

        // All events in a single append call should belong to the same aggregate
        // and thus have the same aggregateId and aggregateType.
        // We'll take these from the first event.
        $firstEvent = $domainEvents[0];
        $aggregateId = $firstEvent->getAggregateId();
        $aggregateType = $firstEvent->getAggregateType();

        $this->pdo->beginTransaction();

        try {
            // Get current version for the aggregate
            $stmt = $this->pdo->prepare(
                "SELECT MAX(version) FROM events WHERE aggregate_id = :aggregate_id"
            );
            $stmt->execute([':aggregate_id' => $aggregateId]);
            $currentVersion = $stmt->fetchColumn();
            $nextVersion = ($currentVersion === false || $currentVersion === null) ? 1 : (int)$currentVersion + 1;

            $insertSql = "INSERT INTO events (event_id, aggregate_id, aggregate_type, event_type, payload, version, occurred_on)
                          VALUES (:event_id, :aggregate_id, :aggregate_type, :event_type, :payload, :version, :occurred_on)";
            $insertStmt = $this->pdo->prepare($insertSql);

            foreach ($domainEvents as $event) {
                if ($event->getAggregateId() !== $aggregateId) {
                    throw new \InvalidArgumentException(
                        'All events in a single append call must belong to the same aggregate ID.'
                    );
                }
                if ($event->getAggregateType() !== $aggregateType) {
                     throw new \InvalidArgumentException(
                        'All events in a single append call must belong to the same aggregate type.'
                    );
                }

                $serialized = $this->serializer->serialize($event);

                // Occurred_on from event, converted to UTC for storage if not already.
                $occurredOn = $event->getOccurredOn()->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s.uP');

                $params = [
                    ':event_id' => $event->getEventId(),
                    ':aggregate_id' => $aggregateId,
                    ':aggregate_type' => $aggregateType,
                    ':event_type' => $serialized['eventType'],
                    ':payload' => json_encode($serialized['payload']),
                    ':version' => $nextVersion,
                    ':occurred_on' => $occurredOn,
                ];

                if (!$insertStmt->execute($params)) {
                    throw new PDOException("Failed to insert event: " . implode(", ", $insertStmt->errorInfo()));
                }
                $nextVersion++;
            }

            $this->pdo->commit();
        } catch (PDOException $e) {
            $this->pdo->rollBack();
            // Consider wrapping PDOException in a custom domain/infrastructure exception
            throw new \RuntimeException("Error appending events to store: " . $e->getMessage(), 0, $e);
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw new \RuntimeException("An unexpected error occurred while appending events: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * @param string $aggregateId
     * @return DomainEventInterface[]
     */
    public function getEventsForAggregate(string $aggregateId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT event_id, aggregate_type, event_type, payload, occurred_on
             FROM events
             WHERE aggregate_id = :aggregate_id
             ORDER BY version ASC"
        );
        $stmt->execute([':aggregate_id' => $aggregateId]);

        $events = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if ($row === false) continue;

            $payload = json_decode($row['payload'], true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                // Handle JSON decode error, maybe log it or throw an exception
                throw new \RuntimeException("Failed to decode JSON payload for event " . $row['event_id']);
            }

            // Ensure occurred_on is DateTimeImmutable
            $occurredOn = new DateTimeImmutable($row['occurred_on']);

            $events[] = $this->deserializer->deserialize(
                $row['event_type'],
                $row['aggregate_type'],
                $payload,
                $row['event_id'],
                $aggregateId, // Passed to deserialize method
                $occurredOn
            );
        }

        return $events;
    }
}

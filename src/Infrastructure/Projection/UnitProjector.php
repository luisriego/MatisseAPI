<?php

declare(strict_types=1);

namespace App\Infrastructure\Projection;

use App\Domain\Event\UnitCreatedEvent;
use App\Domain\Event\OwnerAssignedToUnitEvent;
use App\Domain\Event\OwnerRemovedFromUnitEvent;
use App\Domain\Event\DomainEventInterface;
use PDO;
use PDOException;
use DateTimeZone;

class UnitProjector implements ProjectorInterface
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function supports(DomainEventInterface $event): bool
    {
        return $event instanceof UnitCreatedEvent ||
               $event instanceof OwnerAssignedToUnitEvent ||
               $event instanceof OwnerRemovedFromUnitEvent;
    }

    public function project(DomainEventInterface $event): void
    {
        match (true) {
            $event instanceof UnitCreatedEvent => $this->applyUnitCreated($event),
            $event instanceof OwnerAssignedToUnitEvent => $this->applyOwnerAssignedToUnit($event),
            $event instanceof OwnerRemovedFromUnitEvent => $this->applyOwnerRemovedFromUnit($event),
            default => throw new \LogicException('Unsupported event type passed to UnitProjector: ' . get_class($event))
        };
    }

    private function applyUnitCreated(UnitCreatedEvent $event): void
    {
        $sql = "INSERT INTO units (id, condominium_id, identifier, owner_id, created_at, updated_at)
                VALUES (:id, :condominium_id, :identifier, NULL, :occurred_on, :occurred_on)";

        $stmt = $this->pdo->prepare($sql);
        $occurredOn = $event->getOccurredOn()->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s.uP');

        try {
            $stmt->execute([
                ':id' => $event->getAggregateId(),
                ':condominium_id' => $event->getCondominiumId()->toString(),
                ':identifier' => $event->getIdentifier(),
                ':occurred_on' => $occurredOn,
            ]);
        } catch (PDOException $e) {
            throw new \RuntimeException("Failed to project UnitCreatedEvent: " . $e->getMessage(), 0, $e);
        }
    }

    private function applyOwnerAssignedToUnit(OwnerAssignedToUnitEvent $event): void
    {
        $sql = "UPDATE units SET owner_id = :owner_id, updated_at = :updated_at WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $occurredOn = $event->getOccurredOn()->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s.uP');

        try {
            $stmt->execute([
                ':id' => $event->getAggregateId(),
                ':owner_id' => $event->getOwnerId()->toString(),
                ':updated_at' => $occurredOn,
            ]);
        } catch (PDOException $e) {
            throw new \RuntimeException("Failed to project OwnerAssignedToUnitEvent: " . $e->getMessage(), 0, $e);
        }
    }

    private function applyOwnerRemovedFromUnit(OwnerRemovedFromUnitEvent $event): void
    {
        $sql = "UPDATE units SET owner_id = NULL, updated_at = :updated_at WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $occurredOn = $event->getOccurredOn()->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s.uP');

        try {
            $stmt->execute([
                ':id' => $event->getAggregateId(),
                ':updated_at' => $occurredOn,
            ]);
        } catch (PDOException $e) {
            throw new \RuntimeException("Failed to project OwnerRemovedFromUnitEvent: " . $e->getMessage(), 0, $e);
        }
    }
}

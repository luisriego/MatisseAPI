<?php

declare(strict_types=1);

namespace App\Infrastructure\Projection;

use App\Domain\Event\OwnerCreatedEvent;
use App\Domain\Event\OwnerContactInfoUpdatedEvent;
use App\Domain\Event\DomainEventInterface;
use PDO;
use PDOException;
use DateTimeZone;

class OwnerProjector implements ProjectorInterface
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function supports(DomainEventInterface $event): bool
    {
        return $event instanceof OwnerCreatedEvent ||
               $event instanceof OwnerContactInfoUpdatedEvent;
    }

    public function project(DomainEventInterface $event): void
    {
        match (true) {
            $event instanceof OwnerCreatedEvent => $this->applyOwnerCreated($event),
            $event instanceof OwnerContactInfoUpdatedEvent => $this->applyOwnerContactInfoUpdated($event),
            default => throw new \LogicException('Unsupported event type passed to OwnerProjector: ' . get_class($event))
        };
    }

    private function applyOwnerCreated(OwnerCreatedEvent $event): void
    {
        $sql = "INSERT INTO owners (id, name, email, phone_number, created_at, updated_at)
                VALUES (:id, :name, :email, :phone_number, :occurred_on, :occurred_on)";

        $stmt = $this->pdo->prepare($sql);
        $occurredOn = $event->getOccurredOn()->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s.uP');

        try {
            $stmt->execute([
                ':id' => $event->getAggregateId(),
                ':name' => $event->getName(),
                ':email' => $event->getEmail(),
                ':phone_number' => $event->getPhoneNumber(),
                ':occurred_on' => $occurredOn,
            ]);
        } catch (PDOException $e) {
            throw new \RuntimeException("Failed to project OwnerCreatedEvent: " . $e->getMessage(), 0, $e);
        }
    }

    private function applyOwnerContactInfoUpdated(OwnerContactInfoUpdatedEvent $event): void
    {
        $sql = "UPDATE owners
                SET name = :name, email = :email, phone_number = :phone_number, updated_at = :updated_at
                WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $occurredOn = $event->getOccurredOn()->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s.uP');

        try {
            $stmt->execute([
                ':id' => $event->getAggregateId(),
                ':name' => $event->getNewName(),
                ':email' => $event->getNewEmail(),
                ':phone_number' => $event->getNewPhoneNumber(),
                ':updated_at' => $occurredOn,
            ]);
        } catch (PDOException $e) {
            throw new \RuntimeException("Failed to project OwnerContactInfoUpdatedEvent: " . $e->getMessage(), 0, $e);
        }
    }
}

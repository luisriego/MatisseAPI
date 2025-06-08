<?php

declare(strict_types=1);

namespace App\Infrastructure\Projection;

use App\Domain\Event\CondominiumAddressChangedEvent;
use App\Domain\Event\CondominiumRegisteredEvent;
use App\Domain\Event\CondominiumRenamedEvent;
use App\Domain\Event\DomainEventInterface;
use PDO;
use PDOException; // For error handling
use DateTimeZone; // For UTC conversion

class CondominiumProjector implements ProjectorInterface
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function supports(DomainEventInterface $event): bool
    {
        return $event instanceof CondominiumRegisteredEvent ||
               $event instanceof CondominiumRenamedEvent ||
               $event instanceof CondominiumAddressChangedEvent;
    }

    public function project(DomainEventInterface $event): void
    {
        match (true) {
            $event instanceof CondominiumRegisteredEvent => $this->applyCondominiumRegistered($event),
            $event instanceof CondominiumRenamedEvent => $this->applyCondominiumRenamed($event),
            $event instanceof CondominiumAddressChangedEvent => $this->applyCondominiumAddressChanged($event),
            default => throw new \LogicException('Unsupported event type passed to CondominiumProjector: ' . get_class($event))
        };
    }

    private function applyCondominiumRegistered(CondominiumRegisteredEvent $event): void
    {
        $sql = "INSERT INTO condominiums (id, name, address_street, address_city, address_postal_code, address_country, created_at, updated_at)
                VALUES (:id, :name, :address_street, :address_city, :address_postal_code, :address_country, :occurred_on, :occurred_on)";

        $stmt = $this->pdo->prepare($sql);
        $address = $event->getAddress();
        $occurredOn = $event->getOccurredOn()->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s.uP');

        try {
            $stmt->execute([
                ':id' => $event->getAggregateId(),
                ':name' => $event->getName(),
                ':address_street' => $address->getStreet(),
                ':address_city' => $address->getCity(),
                ':address_postal_code' => $address->getPostalCode(),
                ':address_country' => $address->getCountry(),
                ':occurred_on' => $occurredOn,
            ]);
        } catch (PDOException $e) {
            // Handle error, e.g., log and rethrow or specific error for duplicate ID
            throw new \RuntimeException("Failed to project CondominiumRegisteredEvent: " . $e->getMessage(), 0, $e);
        }
    }

    private function applyCondominiumRenamed(CondominiumRenamedEvent $event): void
    {
        $sql = "UPDATE condominiums SET name = :name, updated_at = :updated_at WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $occurredOn = $event->getOccurredOn()->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s.uP');

        try {
            $stmt->execute([
                ':id' => $event->getAggregateId(),
                ':name' => $event->getNewName(),
                ':updated_at' => $occurredOn,
            ]);
        } catch (PDOException $e) {
            throw new \RuntimeException("Failed to project CondominiumRenamedEvent: " . $e->getMessage(), 0, $e);
        }
    }

    private function applyCondominiumAddressChanged(CondominiumAddressChangedEvent $event): void
    {
        $sql = "UPDATE condominiums
                SET address_street = :address_street,
                    address_city = :address_city,
                    address_postal_code = :address_postal_code,
                    address_country = :address_country,
                    updated_at = :updated_at
                WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $address = $event->getNewAddress();
        $occurredOn = $event->getOccurredOn()->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s.uP');

        try {
            $stmt->execute([
                ':id' => $event->getAggregateId(),
                ':address_street' => $address->getStreet(),
                ':address_city' => $address->getCity(),
                ':address_postal_code' => $address->getPostalCode(),
                ':address_country' => $address->getCountry(),
                ':updated_at' => $occurredOn,
            ]);
        } catch (PDOException $e) {
            throw new \RuntimeException("Failed to project CondominiumAddressChangedEvent: " . $e->getMessage(), 0, $e);
        }
    }
}

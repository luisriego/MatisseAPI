<?php

declare(strict_types=1);

namespace App\Infrastructure\Projection;

use App\Domain\Event\DomainEventInterface;
use App\Domain\Event\FeeAppliedToUnitLedgerEvent;
use PDO;
use PDOException;
use DateTimeZone;

class FeeProjector implements ProjectorInterface
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function supports(DomainEventInterface $event): bool
    {
        return $event instanceof FeeAppliedToUnitLedgerEvent;
    }

    public function project(DomainEventInterface $event): void
    {
        if (!$event instanceof FeeAppliedToUnitLedgerEvent) {
            throw new \LogicException('Unsupported event type passed to FeeProjector.');
        }

        $sql = "INSERT INTO fees_issued (id, unit_id, fee_item_id, description, amount_cents, currency_code, due_date, issued_at, status)
                VALUES (:id, :unit_id, :fee_item_id, :description, :amount_cents, :currency_code, :due_date, :issued_at, :status)";

        $stmt = $this->pdo->prepare($sql);

        $amount = $event->getAmount();
        $issuedAt = $event->getOccurredOn()->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s.uP');
        $dueDate = $event->getDueDate()->format('Y-m-d');
        $description = $event->getDescription(); // This can be null

        try {
            $stmt->execute([
                ':id' => $event->getEventId(), // Using event's ID as PK for the fee_issued record
                ':unit_id' => $event->getAggregateId(), // UnitId
                ':fee_item_id' => $event->getFeeItemId()->toString(),
                ':description' => $description,
                ':amount_cents' => $amount->getAmount(),
                ':currency_code' => $amount->getCurrency()->getCode(),
                ':due_date' => $dueDate,
                ':issued_at' => $issuedAt,
                ':status' => 'PENDING', // Default status
            ]);
        } catch (PDOException $e) {
            throw new \RuntimeException("Failed to project FeeAppliedToUnitLedgerEvent: " . $e->getMessage(), 0, $e);
        }
    }
}

<?php

declare(strict_types=1);

namespace App\Infrastructure\Projection;

use App\Domain\Event\DomainEventInterface;
use App\Domain\Event\ExpenseRecordedEvent;
use PDO;
use PDOException;
use DateTimeZone;

class ExpenseProjector implements ProjectorInterface
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function supports(DomainEventInterface $event): bool
    {
        return $event instanceof ExpenseRecordedEvent;
    }

    public function project(DomainEventInterface $event): void
    {
        if (!$event instanceof ExpenseRecordedEvent) {
            // Should not happen if supports() is called first by ProjectionManager
            throw new \LogicException('Unsupported event type passed to ExpenseProjector.');
        }

        $sql = "INSERT INTO expenses (id, condominium_id, expense_category_id, description, amount_cents, currency_code, expense_date, recorded_at)
                VALUES (:id, :condominium_id, :expense_category_id, :description, :amount_cents, :currency_code, :expense_date, :recorded_at)";

        $stmt = $this->pdo->prepare($sql);

        $amount = $event->getAmount();
        // Event's occurredOn is when the event was recorded in the system.
        // Event's expenseDate is the actual date of the expense.
        $recordedAt = $event->getOccurredOn()->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s.uP');
        $expenseDate = $event->getExpenseDate()->format('Y-m-d'); // Store date part only as per DDL

        try {
            $stmt->execute([
                ':id' => $event->getAggregateId(), // ExpenseId
                ':condominium_id' => $event->getCondominiumId()->toString(),
                ':expense_category_id' => $event->getExpenseCategoryId()->toString(),
                ':description' => $event->getDescription(),
                ':amount_cents' => $amount->getAmount(),
                ':currency_code' => $amount->getCurrency()->getCode(),
                ':expense_date' => $expenseDate,
                ':recorded_at' => $recordedAt,
            ]);
        } catch (PDOException $e) {
            // Handle error, e.g., log and rethrow or specific error for duplicate ID
            throw new \RuntimeException("Failed to project ExpenseRecordedEvent: " . $e->getMessage(), 0, $e);
        }
    }
}

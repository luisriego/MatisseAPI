<?php

declare(strict_types=1);

namespace App\Infrastructure\Projection;

use App\Domain\Event\DomainEventInterface;
use App\Domain\Event\PaymentReceivedOnUnitLedgerEvent;
use PDO;
use PDOException;
use DateTimeZone;

class PaymentProjector implements ProjectorInterface
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function supports(DomainEventInterface $event): bool
    {
        return $event instanceof PaymentReceivedOnUnitLedgerEvent;
    }

    public function project(DomainEventInterface $event): void
    {
        if (!$event instanceof PaymentReceivedOnUnitLedgerEvent) {
            throw new \LogicException('Unsupported event type passed to PaymentProjector.');
        }

        $sql = "INSERT INTO payments_received (id, unit_id, amount_cents, currency_code, payment_date, payment_method, received_at)
                VALUES (:id, :unit_id, :amount_cents, :currency_code, :payment_date, :payment_method, :received_at)";

        $stmt = $this->pdo->prepare($sql);

        $amount = $event->getAmount();
        // Event's occurredOn is when the event was recorded in the system (system timestamp for the payment record).
        $receivedAt = $event->getOccurredOn()->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s.uP');
        // Event's paymentDate is the actual date the payment was made.
        $paymentDate = $event->getPaymentDate()->format('Y-m-d');

        try {
            $stmt->execute([
                ':id' => $event->getPaymentId()->toString(), // Using paymentId from event as PK
                ':unit_id' => $event->getAggregateId(), // UnitId
                ':amount_cents' => $amount->getAmount(),
                ':currency_code' => $amount->getCurrency()->getCode(),
                ':payment_date' => $paymentDate,
                ':payment_method' => $event->getPaymentMethod(),
                ':received_at' => $receivedAt,
            ]);
        } catch (PDOException $e) {
            throw new \RuntimeException("Failed to project PaymentReceivedOnUnitLedgerEvent: " . $e->getMessage(), 0, $e);
        }
    }
}

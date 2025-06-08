<?php

declare(strict_types=1);

namespace App\Infrastructure\Projection;

use App\Domain\Event\DomainEventInterface;
use App\Domain\Event\FeeAppliedToUnitLedgerEvent;
use App\Domain\Event\PaymentReceivedOnUnitLedgerEvent;
use App\Domain\Event\UnitLedgerAccountCreatedEvent;
use App\Domain\ValueObject\Money; // Used for calculations
use PDO;
use PDOException;
use DateTimeZone;

class UnitLedgerAccountProjector implements ProjectorInterface
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function supports(DomainEventInterface $event): bool
    {
        return $event instanceof UnitLedgerAccountCreatedEvent ||
               $event instanceof FeeAppliedToUnitLedgerEvent ||
               $event instanceof PaymentReceivedOnUnitLedgerEvent;
    }

    public function project(DomainEventInterface $event): void
    {
        // For balance updates, wrap in transaction if fetching current balance first
        // However, for simplicity and idempotency, some projectors might directly calculate
        // if events provide enough info, or rely on specific DB features for atomic updates.
        // Here, we'll assume event handlers update the balance directly.
        // A more robust way for balance: store transactions and calculate balance, or use atomic DB operations.

        $this->pdo->beginTransaction();
        try {
            match (true) {
                $event instanceof UnitLedgerAccountCreatedEvent => $this->applyUnitLedgerAccountCreated($event),
                $event instanceof FeeAppliedToUnitLedgerEvent => $this->applyFeeAppliedToUnitLedger($event),
                $event instanceof PaymentReceivedOnUnitLedgerEvent => $this->applyPaymentReceivedOnUnitLedger($event),
                default => throw new \LogicException('Unsupported event type passed to UnitLedgerAccountProjector: ' . get_class($event))
            };
            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            // Log or handle the exception $e
            throw new \RuntimeException("Failed to project event for UnitLedgerAccount: " . $e->getMessage(), 0, $e);
        }
    }

    private function applyUnitLedgerAccountCreated(UnitLedgerAccountCreatedEvent $event): void
    {
        $sql = "INSERT INTO unit_ledger_accounts (unit_id, balance_amount_cents, balance_currency_code, last_updated_at)
                VALUES (:unit_id, :balance_amount_cents, :balance_currency_code, :last_updated_at)";

        $stmt = $this->pdo->prepare($sql);
        $balance = $event->getInitialBalance();
        $occurredOn = $event->getOccurredOn()->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s.uP');

        $stmt->execute([
            ':unit_id' => $event->getAggregateId(),
            ':balance_amount_cents' => $balance->getAmount(),
            ':balance_currency_code' => $balance->getCurrency()->getCode(),
            ':last_updated_at' => $occurredOn,
        ]);
    }

    private function applyFeeAppliedToUnitLedger(FeeAppliedToUnitLedgerEvent $event): void
    {
        // This assumes unit_id is the aggregateId from the event.
        // Fees increase the balance (amount unit owes).
        $sql = "UPDATE unit_ledger_accounts
                SET balance_amount_cents = balance_amount_cents + :fee_amount,
                    last_updated_at = :last_updated_at
                WHERE unit_id = :unit_id AND balance_currency_code = :currency_code";

        $stmt = $this->pdo->prepare($sql);
        $feeAmount = $event->getAmount();
        $occurredOn = $event->getOccurredOn()->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s.uP');

        $stmt->execute([
            ':unit_id' => $event->getAggregateId(),
            ':fee_amount' => $feeAmount->getAmount(),
            ':currency_code' => $feeAmount->getCurrency()->getCode(), // Ensure currency matches
            ':last_updated_at' => $occurredOn,
        ]);
        if ($stmt->rowCount() === 0) {
            // Handle case where unit account doesn't exist or currency mismatch
            // This might indicate an issue if UnitLedgerAccountCreatedEvent wasn't processed first
            // or if currency codes are inconsistent. For now, we'll assume it should exist.
            throw new \RuntimeException("Failed to apply fee: Unit ledger account not found or currency mismatch for unit " . $event->getAggregateId());
        }
    }

    private function applyPaymentReceivedOnUnitLedger(PaymentReceivedOnUnitLedgerEvent $event): void
    {
        // Payments decrease the balance (amount unit owes).
        $sql = "UPDATE unit_ledger_accounts
                SET balance_amount_cents = balance_amount_cents - :payment_amount,
                    last_updated_at = :last_updated_at
                WHERE unit_id = :unit_id AND balance_currency_code = :currency_code";

        $stmt = $this->pdo->prepare($sql);
        $paymentAmount = $event->getAmount();
        $occurredOn = $event->getOccurredOn()->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s.uP');

        $stmt->execute([
            ':unit_id' => $event->getAggregateId(),
            ':payment_amount' => $paymentAmount->getAmount(),
            ':currency_code' => $paymentAmount->getCurrency()->getCode(), // Ensure currency matches
            ':last_updated_at' => $occurredOn,
        ]);
         if ($stmt->rowCount() === 0) {
            throw new \RuntimeException("Failed to apply payment: Unit ledger account not found or currency mismatch for unit " . $event->getAggregateId());
        }
    }
}

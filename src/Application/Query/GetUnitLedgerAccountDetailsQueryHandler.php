<?php

declare(strict_types=1);

namespace App\Application\Query;

use App\Application\DTO\UnitLedgerAccountDetailsDTO;
use PDO;
use PDOException;
use DomainException; // For not found
use DateTimeZone; // For timezone conversion if needed, though TIMESTAMPTZ usually handles it
use DateTimeImmutable;

final class GetUnitLedgerAccountDetailsQueryHandler
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function handle(GetUnitLedgerAccountDetailsQuery $query): UnitLedgerAccountDetailsDTO
    {
        $stmt = $this->pdo->prepare(
            "SELECT unit_id, balance_amount_cents, balance_currency_code, last_updated_at
             FROM unit_ledger_accounts
             WHERE unit_id = :unit_id"
        );

        try {
            $stmt->execute([':unit_id' => $query->unitId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            // Log error $e->getMessage()
            throw new \RuntimeException("Database error while fetching unit ledger account details.", 0, $e);
        }

        if (!$row) {
            throw new DomainException("Unit ledger account for Unit ID {$query->unitId} not found.");
        }

        // Ensure last_updated_at is in a consistent format (ISO8601 with timezone)
        $lastUpdatedAt = new DateTimeImmutable($row['last_updated_at']);
        // $lastUpdatedAt = $lastUpdatedAt->setTimezone(new DateTimeZone('UTC')); // Optional: Convert to UTC if not already

        return new UnitLedgerAccountDetailsDTO(
            $row['unit_id'],
            (int)$row['balance_amount_cents'],
            $row['balance_currency_code'],
            $lastUpdatedAt->format(\DateTimeInterface::ATOM) // ISO8601 format
        );
    }
}

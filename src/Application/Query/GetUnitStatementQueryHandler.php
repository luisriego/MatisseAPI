<?php

declare(strict_types=1);

namespace App\Application\Query;

use App\Application\DTO\FeeIssuedDTO;
use App\Application\DTO\PaymentReceivedDTO;
use App\Application\DTO\UnitLedgerAccountDetailsDTO;
use App\Application\DTO\UnitStatementDTO;
use PDO;
use PDOException;
use DomainException; // For not found
use DateTimeImmutable; // For date formatting

final class GetUnitStatementQueryHandler
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function handle(GetUnitStatementQuery $query): UnitStatementDTO
    {
        // 1. Fetch current balance
        $balanceStmt = $this->pdo->prepare(
            "SELECT unit_id, balance_amount_cents, balance_currency_code, last_updated_at
             FROM unit_ledger_accounts
             WHERE unit_id = :unit_id"
        );
        $balanceStmt->execute([':unit_id' => $query->unitId]);
        $balanceRow = $balanceStmt->fetch(PDO::FETCH_ASSOC);

        if (!$balanceRow) {
            // Or, create a default zero balance DTO if a statement for a non-existent ledger is permissible
            throw new DomainException("Unit ledger account for Unit ID {$query->unitId} not found.");
        }
        $currentBalanceDTO = new UnitLedgerAccountDetailsDTO(
            $balanceRow['unit_id'],
            (int)$balanceRow['balance_amount_cents'],
            $balanceRow['balance_currency_code'],
            (new DateTimeImmutable($balanceRow['last_updated_at']))->format(\DateTimeInterface::ATOM)
        );

        // 2. Fetch fees issued
        $feesStmt = $this->pdo->prepare(
            "SELECT id, fee_item_id, description, amount_cents, currency_code, due_date, issued_at, status
             FROM fees_issued
             WHERE unit_id = :unit_id
             ORDER BY issued_at DESC"
        );
        $feesStmt->execute([':unit_id' => $query->unitId]);
        $feesIssuedDTOs = [];
        while($row = $feesStmt->fetch(PDO::FETCH_ASSOC)) {
            if ($row === false) continue;
            $feesIssuedDTOs[] = new FeeIssuedDTO(
                $row['id'],
                $row['fee_item_id'],
                $row['description'],
                (int)$row['amount_cents'],
                $row['currency_code'],
                (new DateTimeImmutable($row['due_date']))->format('Y-m-d'),
                (new DateTimeImmutable($row['issued_at']))->format(\DateTimeInterface::ATOM),
                $row['status']
            );
        }

        // 3. Fetch payments received
        $paymentsStmt = $this->pdo->prepare(
            "SELECT id, amount_cents, currency_code, payment_date, payment_method, received_at
             FROM payments_received
             WHERE unit_id = :unit_id
             ORDER BY payment_date DESC"
        );
        $paymentsStmt->execute([':unit_id' => $query->unitId]);
        $paymentsReceivedDTOs = [];
        while($row = $paymentsStmt->fetch(PDO::FETCH_ASSOC)) {
            if ($row === false) continue;
            $paymentsReceivedDTOs[] = new PaymentReceivedDTO(
                $row['id'],
                (int)$row['amount_cents'],
                $row['currency_code'],
                (new DateTimeImmutable($row['payment_date']))->format('Y-m-d'),
                $row['payment_method'],
                (new DateTimeImmutable($row['received_at']))->format(\DateTimeInterface::ATOM)
            );
        }

        return new UnitStatementDTO(
            $query->unitId,
            $currentBalanceDTO,
            $feesIssuedDTOs,
            $paymentsReceivedDTOs
        );
    }
}

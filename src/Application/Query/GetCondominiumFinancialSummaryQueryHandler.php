<?php

declare(strict_types=1);

namespace App\Application\Query;

use App\Application\DTO\CondominiumFinancialSummaryDTO;
use PDO;
use PDOException;
use DomainException;

final class GetCondominiumFinancialSummaryQueryHandler
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function handle(GetCondominiumFinancialSummaryQuery $query): CondominiumFinancialSummaryDTO
    {
        // Validate Condominium exists (optional, or let queries return zero if no data)
        // $condoStmt = $this->pdo->prepare("SELECT id FROM condominiums WHERE id = :id");
        // $condoStmt->execute([':id' => $query->condominiumId]);
        // if (!$condoStmt->fetch()) {
        //     throw new DomainException("Condominium with ID {$query->condominiumId} not found.");
        // }

        $params = [':condominium_id' => $query->condominiumId];
        $dateConditionsIncome = "";
        $dateConditionsExpenses = "";

        if ($query->periodStartDate) {
            $params[':start_date'] = $query->periodStartDate;
            // Assuming payments_received.payment_date and expenses.expense_date
            $dateConditionsIncome .= " AND pr.payment_date >= :start_date";
            $dateConditionsExpenses .= " AND e.expense_date >= :start_date";
        }
        if ($query->periodEndDate) {
            $params[':end_date'] = $query->periodEndDate;
            $dateConditionsIncome .= " AND pr.payment_date <= :end_date";
            $dateConditionsExpenses .= " AND e.expense_date <= :end_date";
        }

        // Calculate Total Income
        // Income is based on payments received by units belonging to the condominium.
        // This requires joining units with payments_received.
        $incomeSql = "SELECT SUM(pr.amount_cents) as total_income, MAX(pr.currency_code) as currency_code
                      FROM payments_received pr
                      JOIN units u ON pr.unit_id = u.id
                      WHERE u.condominium_id = :condominium_id {$dateConditionsIncome}";

        $incomeStmt = $this->pdo->prepare($incomeSql);
        $incomeStmt->execute($params);
        $incomeResult = $incomeStmt->fetch(PDO::FETCH_ASSOC);
        $totalIncomeCents = (int)($incomeResult['total_income'] ?? 0);
        $currencyCode = $incomeResult['currency_code'] ?? 'USD'; // Default currency, or error if none

        // Calculate Total Expenses
        // Expenses are directly linked to condominium_id in the 'expenses' table.
        $expenseSql = "SELECT SUM(e.amount_cents) as total_expenses, MAX(e.currency_code) as currency_code
                       FROM expenses e
                       WHERE e.condominium_id = :condominium_id {$dateConditionsExpenses}";

        $expenseStmt = $this->pdo->prepare($expenseSql);
        $expenseStmt->execute($params); // Same params should work if :start_date, :end_date are bound
        $expenseResult = $expenseStmt->fetch(PDO::FETCH_ASSOC);
        $totalExpensesCents = (int)($expenseResult['total_expenses'] ?? 0);

        // If currency codes differ between income and expenses, that's a problem for a simple summary.
        // For now, we use the currency code from income, or from expenses if income is zero.
        if ($totalIncomeCents === 0 && $totalExpensesCents !== 0 && isset($expenseResult['currency_code'])) {
            $currencyCode = $expenseResult['currency_code'];
        }
        // A more robust solution would involve currency conversion or separate summaries per currency.

        $netBalanceCents = $totalIncomeCents - $totalExpensesCents;

        return new CondominiumFinancialSummaryDTO(
            $query->condominiumId,
            $totalIncomeCents,
            $totalExpensesCents,
            $netBalanceCents,
            $currencyCode
        );
    }
}

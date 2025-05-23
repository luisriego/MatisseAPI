<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Expense;
use Psr\Log\LoggerInterface;

readonly class MonthlyExpenseAggregatorService
{
    public function __construct(private LoggerInterface $logger)
    {
    }

    /**
     * Agrega los gastos mensuales por tipo de distribución.
     *
     * @param array<Expense> $monthlyExpenses
     * @return array{
     *     equal: int,
     *     fraction: int,
     *     individual: int,
     *     grandTotal: int
     * }
     */
    public function aggregateTotals(array $monthlyExpenses): array
    {
        $totals = [
            'equal' => 0,
            'fraction' => 0,
            'individual' => 0,
            'grandTotal' => 0,
        ];

        if (empty($monthlyExpenses)) {
            return $totals;
        }

        foreach ($monthlyExpenses as $expense) {
            if (!$expense instanceof Expense) {
                $this->logger->warning(sprintf('[MonthlyExpenseAggregator] Elemento inesperado en monthlyExpenses, se esperaba App\Entity\Expense, se obtuvo %s.', get_debug_type($expense)));
                continue;
            }

            $expenseType = $expense->type();
            if (!$expenseType) {
                $errorMessage = sprintf(
                    '[MonthlyExpenseAggregator] Despesa "%s" (ID: %s) não tem um tipo registrado. Não será contabilizada nos totais.',
                    $expense->description() ?? 'N/D',
                    $expense->id()
                );
                $this->logger->error($errorMessage);
                // Podrías decidir lanzar una excepción aquí si es un error crítico
                // throw new \RuntimeException($errorMessage);
                continue; // O simplemente omitirla de los totales
            }

            $amount = $expense->amount();
            $totals['grandTotal'] += $amount;

            switch (strtoupper((string)$expenseType->distributionMethod())) {
                case 'EQUAL':
                    $totals['equal'] += $amount;
                    break;
                case 'FRACTION':
                    $totals['fraction'] += $amount;
                    break;
                default: // Incluye 'INDIVIDUAL' y cualquier otro método no reconocido
                    $totals['individual'] += $amount;
                    break;
            }
        }
        return $totals;
    }
}

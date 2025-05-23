<?php

// src/Service/RecurringExpenseCheckerService.php
declare(strict_types=1);

namespace App\Service;

use App\Entity\RecurringExpense;
use App\Repository\ExpenseRepository;
use App\Repository\RecurringExpenseRepository;
use Psr\Log\LoggerInterface;

readonly class RecurringExpenseCheckerService
{
    public function __construct(
        private RecurringExpenseRepository $recurringExpenseRepository,
        private ExpenseRepository $expenseRepository,
        private LoggerInterface $logger
    ) {
    }

    /**
     * Verifica para cuáles RecurringExpense activos en el mes y año dados
     * aún no se ha creado una instancia de Expense.
     *
     * @param int $year El año para la verificación.
     * @param int $month El mes para la verificación.
     * @return RecurringExpense[] Un array de entidades RecurringExpense para las cuales falta una instancia de Expense.
     */
    public function getMissingInstances(int $year, int $month): array
    {
        $missingInstancesFor = [];

        // 1. Obtiene todas las entidades RecurringExpense que deberían estar activas este mes
        //    (según su configuración de 'monthsOfYear' y 'isActive').
        $activeRecurringForMonth = $this->recurringExpenseRepository->findActivesForThisMonth($month);

        if (empty($activeRecurringForMonth)) {
            $this->logger->info(sprintf('[RecurringExpenseChecker] No se encontraron gastos recurrentes activos configurados para el mes %d.', $month));
            return [];
        }

        $this->logger->info(sprintf('[RecurringExpenseChecker] Encontrados %d gastos recurrentes activos configurados para el mes %d. Verificando instancias...', count($activeRecurringForMonth), $month));

        foreach ($activeRecurringForMonth as $recurringExpense) {
            // 2. Para cada RecurringExpense activo, verifica si ya existe una instancia de Expense
            //    en el mes/año especificados y que esté vinculada a este RecurringExpense.
            //    También considera el rango de fechas de validez del RecurringExpense.

            if (!$this->shouldBeActiveInPeriod($recurringExpense, $year, $month)) {
                $this->logger->debug(sprintf('[RecurringExpenseChecker] Gasto recurrente "%s" (ID: %s) no está activo en el período %d-%02d según sus startDate/endDate. Omitiendo.', $recurringExpense->description() ?? 'N/D', $recurringExpense->id(), $year, $month));
                continue;
            }

            if (!$this->expenseRepository->hasInstanceForRecurringExpenseAndMonth($recurringExpense, $year, $month)) {
                $this->logger->warning(sprintf('[RecurringExpenseChecker] Falta instancia de Expense para el gasto recurrente "%s" (ID: %s) en %d-%02d.', $recurringExpense->description() ?? 'N/D', $recurringExpense->id(), $year, $month));
                $missingInstancesFor[] = $recurringExpense;
            } else {
                $this->logger->info(sprintf('[RecurringExpenseChecker] Instancia de Expense encontrada para el gasto recurrente "%s" (ID: %s) en %d-%02d.', $recurringExpense->description() ?? 'N/D', $recurringExpense->id(), $year, $month));
            }
        }

        return $missingInstancesFor;
    }

    /**
     * Verifica si un RecurringExpense debería estar activo basado en sus fechas startDate y endDate
     * para un año y mes específicos.
     */
    private function shouldBeActiveInPeriod(RecurringExpense $recurringExpense, int $year, int $month): bool
    {
        $targetPeriodStart = new \DateTimeImmutable(sprintf('%d-%02d-01', $year, $month));
        $targetPeriodEnd = $targetPeriodStart->modify('last day of this month');

        $reStartDate = $recurringExpense->startDate();
        $reEndDate = $recurringExpense->endDate();

        // El gasto recurrente debe haber comenzado antes o durante el final del período objetivo
        if ($reStartDate > $targetPeriodEnd) {
            return false;
        }

        // Si hay una fecha de fin, el gasto recurrente no debe haber terminado antes del inicio del período objetivo
        if ($reEndDate !== null && $reEndDate < $targetPeriodStart) {
            return false;
        }

        return true;
    }
}

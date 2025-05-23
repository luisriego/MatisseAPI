<?php

// src/Service/GenerateRecurringExpenseService.php
namespace App\Service;

use App\Entity\Expense;
use App\Entity\RecurringExpense; // <--- Cambiado de RecurringExpenseDefinition
use App\Repository\ExpenseRepository;
use App\Repository\RecurringExpenseRepository; // <--- Cambiado de RecurringExpenseDefinitionRepository
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Uid\Uuid;

readonly class GenerateRecurringExpenseService
{
    public function __construct(
        private EntityManagerInterface     $entityManager,
        private RecurringExpenseRepository $recurringExpenseRepository,
        private ExpenseRepository          $expenseRepository,
        private LoggerInterface            $logger
    ) {
    }

    public function generateInstancesForMonth(\DateTimeInterface $targetProcessingDate): array
    {
        $targetYear = (int) $targetProcessingDate->format('Y');
        $targetMonth = (int) $targetProcessingDate->format('m');

        $this->logger->info(sprintf(
            '[RecurringExpenses] Iniciando geração de instancias para %d-%02d.',
            $targetYear,
            $targetMonth
        ));

        $activeRecurringExpenses = $this->recurringExpenseRepository->findActivesForThisMonth($targetYear, $targetMonth);

        $createdInstancesCount = 0;
        $processedDefinitionsCount = 0;
        $errors = [];

        foreach ($activeRecurringExpenses as $recurringExpense) {
            $processedDefinitionsCount++;
            if (!$recurringExpense instanceof RecurringExpense) {
                continue;
            }

            if ($this->shouldGenerateInstance($recurringExpense, $targetProcessingDate)) {
                // El método en ExpenseRepository ahora debe verificar usando la expenseDate
                if ($this->expenseRepository->hasInstanceForRecurringExpenseAndMonth($recurringExpense, $targetYear, $targetMonth)) {
                    $this->logger->info(sprintf(
                        '[RecurringExpenses] Instancia para definição recorrente ID %s ya existe para %d-%02d. Omitindo.',
                        $recurringExpense->id(),
                        $targetYear,
                        $targetMonth
                    ));
                    continue;
                }

                try {
                    // Calcular la fecha del gasto dentro del mes objetivo
                    /** @var \DateTime $expenseDateForInstance */
                    $expenseDateForInstance = $this->calculateExpenseDateForMonth($recurringExpense, $targetYear, $targetMonth);

                    // Usar el método create de Expense y luego los setters
                    $expense = Expense::create(
                        Uuid::v4()->toRfc4122(), // Asumiendo que Expense usa Uuid
                        $recurringExpense->amount(),
                        $recurringExpense->expenseType(),
                        null,
                        $expenseDateForInstance,
                    );

                    $expense->addDescription($recurringExpense->description() . sprintf(' (Recorrente %d-%02d)', $targetYear, $targetMonth));
                    $expense->setRecurringExpense($recurringExpense);

                    $this->entityManager->persist($expense);
                    $createdInstancesCount++;
                    $this->logger->info(sprintf(
                        '[RecurringExpenses] Instancia de despesa criada para definição recorrente ID %s (Descrição: %s) para %d-%02d.',
                        $recurringExpense->id(),
                        $expense->description(),
                        $targetYear,
                        $targetMonth
                    ));


                    if ($recurringExpense->occurrencesLeft() !== null) {
                        $recurringExpense->occurrencesLeft($recurringExpense->occurrencesLeft() - 1);
                        if ($recurringExpense->occurrencesLeft() <= 0) {
                            $recurringExpense->deactivate();
                            $this->logger->info(sprintf(
                                '[RecurringExpenses] Definição recorrente ID %s desativada, ocorrências esgotadas.',
                                $recurringExpense->id()
                            ));
                        }
                        $this->entityManager->persist($recurringExpense);
                    }
                } catch (\Exception $e) {
                    $errorMessage = sprintf(
                        '[RecurringExpenses] Erro criando instância para definição recorrente ID %s: %s',
                        $recurringExpense->id(),
                        $e->getMessage()
                    );
                    $this->logger->error($errorMessage, ['exception' => $e]);
                    $errors[] = $errorMessage;
                }
            }
        }

        if ($createdInstancesCount > 0 || !empty($errors)) {
            try {
                $this->entityManager->flush();
            } catch (\Exception $e) {
                $errorMessage = sprintf(
                    '[RecurringExpenses] Erro na execução do flush: %s',
                    $e->getMessage()
                );
                $this->logger->critical($errorMessage, ['exception' => $e]);
                $errors[] = $errorMessage;
            }
        }

        $summaryMessage = sprintf(
            '%d definições recorrentes processadas. %d novas instâncias de despesas criadas para %d-%02d.',
            $processedDefinitionsCount,
            $createdInstancesCount,
            $targetYear,
            $targetMonth
        );
        if (!empty($errors)) {
            $summaryMessage .= " Acharam-se erros: " . implode("; ", $errors);
        }

        $this->logger->info("[RecurringExpenses] Finalizado. " . $summaryMessage);

        return [
            'success' => empty($errors),
            'message' => $summaryMessage,
            'createdInstancesCount' => $createdInstancesCount,
            'processedDefinitionsCount' => $processedDefinitionsCount,
            'errors' => $errors,
        ];
    }

    /**
     * Calcula la fecha del gasto para la instancia que se va a generar.
     *
     * @param RecurringExpense $recurringExpense La definición del gasto recurrente.
     * @param int $targetYear El año para el cual se genera la instancia.
     * @param int $targetMonth El mes para el cual se genera la instancia.
     * @return \DateTimeImmutable La fecha del gasto.
     */
    private function calculateExpenseDateForMonth(RecurringExpense $recurringExpense, int $targetYear, int $targetMonth): \DateTimeImmutable
    {
        $dayToSet = $recurringExpense->dueDay();

        // Sure the day will be less than the last day of the month
        $firstDayOfTargetMonth = new \DateTimeImmutable("$targetYear-$targetMonth-01");
        $daysInMonth = (int) $firstDayOfTargetMonth->format('t');
        $actualDay = min($dayToSet, $daysInMonth);

        return new \DateTimeImmutable("$targetYear-$targetMonth-$actualDay");
    }

    /**
     * Determina si se debe generar una instancia de un gasto recurrente para la fecha objetivo.
     *
     * @param RecurringExpense $recurringExpense La definición del gasto recurrente.
     * @param \DateTimeInterface $targetDate La fecha para la cual se está evaluando la generación.
     * @return bool True si se debe generar, false en caso contrario.
     */
    private function shouldGenerateInstance(RecurringExpense $recurringExpense, \DateTimeInterface $targetDate): bool
    {
        // Asegurarse de que targetDate es el primer día del mes para comparaciones consistentes
        $normalizedTargetDate = $targetDate->modify('first day of this month')->setTime(0,0,0);
        $targetTimestamp = $normalizedTargetDate->getTimestamp();

        $startDate = $recurringExpense->startDate()->setTime(0,0,0); // Normalize at the start of the day
        $startTimestamp = $startDate->getTimestamp();

        // 1. Check Start Date
        if ($targetTimestamp < $startTimestamp) {
            return false;
        }

        // 2. Check End Date
        if ($recurringExpense->endDate()) {
            $endDate = $recurringExpense->endDate()->setTime(23,59,59); // Normaliza at the end of the day
            if ($targetTimestamp > $endDate->getTimestamp()) {
                return false;
            }
        }

        // 3. Check Occurrences Left
        if ($recurringExpense->occurrencesLeft() !== null && $recurringExpense->occurrencesLeft() <= 0) {
            return false;
        }

        $targetYear = (int) $normalizedTargetDate->format('Y');
        $targetMonth = (int) $normalizedTargetDate->format('m');

        // Prepare start date of definition for month-based interval calculations (first day of its month)
        $definitionStartMonthDate = $recurringExpense->startDate()->modify('first day of this month')->setTime(0,0,0);

        switch ($recurringExpense->frequency()) {
            case RecurringExpense::FREQUENCY_MONTHLY:
                return true; // Si pasó los chequeos de fecha y ocurrencias

            case RecurringExpense::FREQUENCY_BIMONTHLY: // Cada 2 meses
            case RecurringExpense::FREQUENCY_QUARTERLY: // Cada 3 meses
                $monthsPerPeriod = (RecurringExpense::FREQUENCY_BIMONTHLY === $recurringExpense->frequency()) ? 2 : 3;
                // Calculamos la diferencia en meses desde el inicio de la recurrencia hasta el mes objetivo
                $diff = $definitionStartMonthDate->diff($normalizedTargetDate);
                $monthsPassed = ($diff->y * 12) + $diff->m;
                return ($monthsPassed % $monthsPerPeriod === 0);

            case RecurringExpense::FREQUENCY_SEMIANNUALLY: // Cada 6 meses
            case RecurringExpense::FREQUENCY_ANNUALLY:
                $monthsOfYear = $recurringExpense->getMonthsOfYear(); // Array de números de mes [1-12]
                if (empty($monthsOfYear)) {
                    // Si es anual/semianual y no tiene meses definidos, podría tomar el mes de startDate
                    if ($recurringExpense->frequency() === RecurringExpense::FREQUENCY_ANNUALLY) {
                        return $targetMonth === (int)$recurringExpense->startDate()->format('m');
                    }
                    // Para SEMIANNUALLY sin monthsOfYear, la lógica es ambigua.
                    // Es mejor requerir que monthsOfYear esté definido para estas frecuencias.
                    $this->logger->warning(sprintf(
                        '[RecurringExpenses] Definición recurrente ID %s (%s) no tiene monthsOfYear definidos. No se generará.',
                        $recurringExpense->id(), $recurringExpense->frequency()
                    ));
                    return false;
                }
                return in_array($targetMonth, $monthsOfYear, true);

            default:
                $this->logger->warning(sprintf(
                    '[RecurringExpenses] Frecuencia desconocida "%s" para definición recurrente ID %s.',
                    $recurringExpense->frequency(),
                    $recurringExpense->id()
                ));
                return false;
        }
    }
}

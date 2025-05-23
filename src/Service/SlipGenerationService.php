<?php

namespace App\Service;

use App\Entity\Slip;
use App\Repository\ExpenseRepository;
use App\Repository\ResidentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Uid\Uuid;
use App\Service\MonthlyExpenseAggregatorService;

readonly class SlipGenerationService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger,
        private ExpenseRepository $expenseRepository,
        private ResidentRepository $residentRepository,
        private SlipAmountCalculatorService $slipAmountCalculator,
        private RecurringExpenseCheckerService $recurringExpenseChecker,
        private DueDateCalculatorService $dueDateCalculator,
        private MonthlyExpenseAggregatorService $monthlyExpenseAggregator
    ) {
    }

    public function generateSlipsForMonth(\DateTimeInterface $targetMonthDate, bool $forceGenerationDespiteMissingRecurrents = false): array
    {
        $year = (int)$targetMonthDate->format('Y');
        $month = (int)$targetMonthDate->format('m');

        $formatter = new \IntlDateFormatter(
            'pt_BR', \IntlDateFormatter::LONG, \IntlDateFormatter::NONE,
            $targetMonthDate->getTimezone()->getName(), \IntlDateFormatter::GREGORIAN, "MMMM 'de' yyyy"
        );
        $monthName = $formatter->format($targetMonthDate);

        $this->logger->info(sprintf('Iniciando generación de boletos para %s', $monthName));
        $startDate = $targetMonthDate->modify('first day of this month')->setTime(0, 0, 0);
        $endDate = $targetMonthDate->modify('last day of this month')->setTime(23, 59, 59);

        $monthlyExpenses = $this->expenseRepository->findExpensesBetweenDates($startDate, $endDate);

        if (empty($monthlyExpenses)) {
            $message = sprintf('Não foram encontradas despesas para %s. Nenhum boleto será gerado.', $monthName);
            $this->logger->info($message);
            return ['success' => true, 'message' => $message, 'slipsData' => [], 'slipsCount' => 0];
        }

        $expenseTotals = $this->monthlyExpenseAggregator->aggregateTotals($monthlyExpenses);
        $totalExpensesEqualDistribution = $expenseTotals['equal'];
        $totalExpensesFractionDistribution = $expenseTotals['fraction'];
        // $totalExpensesIndividual = $expenseTotals['individual']; // Descomentar si se usa más adelante
        // $grandTotalExpenses = $expenseTotals['grandTotal']; // Descomentar si se usa más adelante


        $this->logger->info(sprintf('Soma total de despesas registradas em %s: %.2f', $monthName, $expenseTotals['grandTotal'] / 100));
        $this->logger->info(sprintf('Soma de despesas distribuídas igualmente: %.2f', $totalExpensesEqualDistribution / 100));
        $this->logger->info(sprintf('Soma de despesas por fração ideal: %.2f', $totalExpensesFractionDistribution / 100));
        $this->logger->info(sprintf('Soma de despesas individuais/não distribuídas: %.2f', $expenseTotals['individual'] / 100));


        $missingRecurringExpenseInstances = $this->recurringExpenseChecker->getMissingInstances($year, $month);

        if (!empty($missingRecurringExpenseInstances)) {
            $missingDescriptions = array_map(fn($re) => $re->description() ?: ('ID Recurrente: ' . $re->id()), $missingRecurringExpenseInstances);

            // --- CAMBIO AQUÍ: Incluir la lista en el mensaje base ---
            $baseMessage = sprintf(
                'Faltam instâncias de Expense para os seguintes gastos recorrentes esperados em %s: %s. Geração de boletos interrompida.',
                $monthName,
                implode(', ', $missingDescriptions) // <-- AÑADIMOS LA LISTA AQUÍ
            );
            // --- FIN CAMBIO ---

            // Loguear el error, incluyendo los detalles en el contexto (opcional, pero útil)
            $this->logger->error($baseMessage, ['missing_recurrent_expenses' => $missingDescriptions]);

            // Devolver un resultado de falla, incluyendo los detalles
            return [
                'success' => false,
                'message' => $baseMessage . ' Favor revisar os gastos faltantes antes de tentar novamente.', // El mensaje ya contiene la lista
                'slipsData' => [],
                'slipsCount' => 0,
                'reason' => 'missing_recurrent_expenses',
                'details' => $missingDescriptions // Incluir los detalles en el retorno sigue siendo útil
            ];
        }

        $residents = $this->residentRepository->findAllPayers();
        if (empty($residents)) {
            $message = sprintf('Não foram encontrados residentes pagadores. Nenhum boleto será gerado para %s.', $monthName);
            $this->logger->warning($message);
            return ['success' => true, 'message' => $message, 'slipsData' => [], 'slipsCount' => 0];
        }
        $numberOfPayingResidents = count($residents);

        $generatedSlipsData = [];
        $slipsPersistedCount = 0;

        $this->entityManager->beginTransaction();
        try {
            foreach ($residents as $resident) {
                $baseSlipAmountInCents = $this->slipAmountCalculator->calculateBaseAmount(
                    $resident,
                    $totalExpensesEqualDistribution,
                    $totalExpensesFractionDistribution,
                    $numberOfPayingResidents
                );

                $gasRestitutionInCents = 0; // Placeholder
                $slipAmountInCents = $baseSlipAmountInCents + $gasRestitutionInCents;

                if ($slipAmountInCents <= 0) {
                    $this->logger->info(sprintf('Valor do boleto para %s (ID: %s) é zero ou negativo (%.2f). Boleto não será gerado.', $resident->unit(), $resident->id(), $slipAmountInCents / 100));
                    continue;
                }

                $dueDate = $this->dueDateCalculator->fifthBusinessDayOfMonth($targetMonthDate);
                $slip = Slip::create(Uuid::v4()->toRfc4122(), $slipAmountInCents, $dueDate);
                $slip->setResidence($resident);
                $this->entityManager->persist($slip);

                $generatedSlipsData[] = ['id' => $slip->id(), 'residentId' => $slip->residence()->id(), 'unit' => $slip->residence()->unit(), 'amount' => $slip->amount(), 'dueDate' => $slip->dueDate()->format('Y-m-d'), 'month' => $month, 'year' => $year];
                $slipsPersistedCount++;
                $this->logger->info(sprintf('Boleto gerado para %s (ID: %s), Valor: %.2f, Vencimento: %s', $resident->unit(), $slip->id(), $slip->amount() / 100, $slip->dueDate()->format('d/m/Y')));
            }

            if ($slipsPersistedCount > 0) {
                $this->entityManager->flush();
                $this->entityManager->commit();
                $message = "{$slipsPersistedCount} boletos gerados com sucesso para {$monthName}.";
                $this->logger->info($message);
                return ['success' => true, 'message' => $message, 'slipsData' => $generatedSlipsData, 'slipsCount' => $slipsPersistedCount];
            }

            $this->entityManager->rollback();
            $message = "Nenhum boleto foi gerado para {$monthName} pois os valores calculados foram zero ou não havia despesas/residentes elegíveis.";
            $this->logger->info($message);
            return ['success' => true, 'message' => $message, 'slipsData' => [], 'slipsCount' => 0];

        } catch (\Exception $e) {
            $this->entityManager->rollback();
            $this->logger->error(sprintf('Erro durante a geração ou persistência dos boletos para %s: %s', $monthName, $e->getMessage()), ['exception' => $e]);
            return ['success' => false, 'message' => sprintf('Erro ao gerar boletos para %s: %s', $monthName, $e->getMessage()), 'slipsData' => [], 'slipsCount' => 0];
        }
    }
}

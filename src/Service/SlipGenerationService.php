<?php

namespace App\Service;

use App\Entity\Resident;
use App\Entity\Slip;
use App\Repository\ExpenseRepository;
use App\Repository\ResidentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Uid\Uuid;


class SlipGenerationService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface        $logger,
        private readonly ExpenseRepository      $expenseRepository,
        private readonly ResidentRepository     $residentRepository,
    ) {
    }

    public function generateSlipsForMonth(\DateTimeInterface $targetMonthDate): array
    {
        $year = (int)$targetMonthDate->format('Y');
        $month = (int)$targetMonthDate->format('m');

        $formatter = new \IntlDateFormatter(
            'pt_BR',
            \IntlDateFormatter::LONG,
            \IntlDateFormatter::NONE,
            $targetMonthDate->getTimezone()->getName(),
            \IntlDateFormatter::GREGORIAN,
            "MMMM 'de' yyyy"
        );
        $monthName = $formatter->format($targetMonthDate);

        $this->logger->info(sprintf('Iniciando generación de boletos para %s', $monthName));

        $startDate = $targetMonthDate->modify('first day of this month')->setTime(0, 0, 0);
        $endDate = $targetMonthDate->modify('last day of this month')->setTime(23, 59, 59);

        $monthlyExpenses = $this->expenseRepository->findExpensesBetweenDates($startDate, $endDate);

        if (empty($monthlyExpenses)) {
            $message = sprintf('Não foram encontradas despesas para %s. Nenhum boleto será gerado.', $monthName);
            $this->logger->info($message);
            return [
                'success' => true, // El proceso se considera "exitoso" en el sentido de que no hubo error técnico, pero no se generó nada.
                'message' => $message,
                'slipsData' => [],
                'slipsCount' => 0
            ];
        }

        $totalExpensesEqualDistribution = 0;
        $totalExpensesFractionDistribution = 0;
        $totalExpensesIndividual = 0;

        foreach ($monthlyExpenses as $expense) {
            $expenseType = $expense->type();
            if (!$expenseType) {
                $this->logger->warning(sprintf(
                    'Despesa %s não tem um tipo registrado, portanto não tenho como saber onde computá-la',
                    $expense->description()));
                throw new \RuntimeException(sprintf(
                    'Despesa %s não tem um tipo registrado, portanto não tenho como saber onde computá-la',
                    $expense->description()));
            }

            switch ($expenseType->distributionMethod()) {
                case 'EQUAL':
                    $totalExpensesEqualDistribution += $expense->amount();
                    break;
                case 'FRACTION':
                    $totalExpensesFractionDistribution += $expense->amount();
                    break;
                default:
                    $totalExpensesIndividual += $expense->amount();
            }

            $this->logger->info(sprintf(
                'Soma total de despesas registradas em %s: %.2f',
                $monthName, $totalExpensesEqualDistribution + $totalExpensesFractionDistribution + $totalExpensesIndividual));
            $this->logger->info(sprintf(
                'Soma de despesas distribuídas igualmente: %.2f',
                $totalExpensesEqualDistribution));
            $this->logger->info(sprintf(
                'Soma de despesas por fração ideal: %.2f',
                $totalExpensesFractionDistribution));
        }

        $missingRecurringExpenses = $this->checkRecurringExpenses($monthlyExpenses, $month);
        if (!empty($missingRecurringExpenses)) {
            $this->logger->warning('Faltam os seguintes gastos recurrentes esperados: '. implode(', ', $missingRecurringExpenses));;
        }

        $residents = $this->residentRepository->findAllPayers();

        $generatedSlipsData = [];
        $slipsPersistedCount = 0;
        $slipsGenerated = 0;
        $this->entityManager->beginTransaction();
        try {
            foreach ($residents as $resident) {
                $baseSlipAmountInCents = $this->calculateBaseSlipAmountForExpenses($resident, $monthlyExpenses);

                $gasRestitutionInCents = 0; // consumo de gas deve ser uma entidade, estudar

                $slipAmountInCents = $baseSlipAmountInCents + $gasRestitutionInCents;

                $dueDate = $targetMonthDate->modify('first day of next month')->modify('+7 days');

                $slip = Slip::create(
                    Uuid::v4()->toRfc4122(),
                    $slipAmountInCents,
                    $dueDate,
                );

                $slip->setResidence($resident);

                $this->entityManager->persist($slip);

                if ($slip) {
                    $generatedSlipsData[] = [
                        'id' => $slip->id(),
                        'residentId' => $slip->residence()->id(),
                        'unit' => $slip->residence()->unit(),
                        'amount' => $slip->amount(),
                        'dueDate' => $slip->dueDate()->format('Y-m-d'),
                        'month' => (int)$targetMonthDate->format('m'),
                        'year' => (int)$targetMonthDate->format('Y'),

                    ];
                    $slipsPersistedCount++;
                }

                $slipsGenerated++;
                $this->logger->info(sprintf('Boleto gerado para %s (ID: %s)', $resident->unit(), $slip->id()));
            }

            if ($slipsGenerated === 5) {
                $this->entityManager->flush();
                $this->entityManager->commit();
                $message = "{$slipsPersistedCount} 5 boletos gerados com sucesso para {$targetMonthDate->format('F Y')}.";
                $this->logger->info($message);;
                return [
                    'success' => true,
                    'message' => $message,
                    'slipsData' => $generatedSlipsData,
                    'slipsCount' => $slipsPersistedCount
                ];
            }

            $this->entityManager->rollback();
            $this->logger->info('Houve problemas ao gerar os boletos, veja mais informação a seguir: ');
            return [
                'success' => true,
                'message' => 'Houve problemas ao gerar os boletos, veja mais informação a seguir: ',
                'slipsData' => [],
                'slipsCount' => 0
            ];
        } catch (\Exception $e) {
            $this->entityManager->rollback();
            $this->logger->info('Houve problemas ao gerar os boletos, veja mais informação a seguir: ');
            return [
                'success' => false,
                'message' => 'Houve problemas ao gerar os boletos, veja mais informação a seguir: ',
                'slipsData' => [],
                'slipsCount' => 0
            ];
        }
    }

    private function checkRecurringExpenses(array $monthlyExpenses, $month): array
    {
        $missing = [];
        $expectedRecurringDescriptions = $this->expenseRepository->findRecurringExpenses($month);
        $foundExpenseDescriptions = [];
        foreach ($monthlyExpenses as $expense) {
            $foundExpenseDescriptions[] = $expense->description();
        }

        foreach ($expectedRecurringDescriptions as $expected) {
            if (!in_array($expected, $foundExpenseDescriptions, true)) {
                $missing[] = $expected;
            }
        }
        return $missing;
    }

    private function calculateBaseSlipAmountForExpenses(
        Resident $resident,
        array $monthlyExpenses
    ): int
    {
        $residentSlipAmountInCents = 0;
        $sumOfEquallyDividedExpensesInCents = 0;
        $sumOfFractionBasedExpensesInCents = 0;

        foreach ($monthlyExpenses as $expense) {
            $expenseType = $expense->type();
            if (!$expenseType) {
                $this->logger->warning(sprintf('Despesa %s não tem um tipo registrado, portanto não tenho como saber onde computá-la.', $expense->id()));
                continue;
            }

            $distributionMethod = $expenseType->distributionMethod();

            switch (strtoupper((string)$distributionMethod)) {
                case 'EQUAL':
                    $sumOfEquallyDividedExpensesInCents += $expense->amount();
                    break;
                case 'FRACTION':
                    $sumOfFractionBasedExpensesInCents += $expense->amount();
                    break;
                default:
                    $this->logger->info(sprintf(
                        '  - Despesa "%s" (Tipo: "%s", Importe: %.2f) tem um método de distribuição "%s" não aplicável para cálculo base. Será omitida.',
                        $expense->description(),
                        $expenseType->name(),
                        $expense->amount() / 100,
                        $distributionMethod
                    ));
                    break;
            }
        }

        $amountPerResident = (int)round($sumOfEquallyDividedExpensesInCents / 5);
        $residentSlipAmountInCents += $amountPerResident;
        $this->logger->info(sprintf(
            '    + Despesas igualmente divididas para %s: %.2f',
            $resident->unit(), $amountPerResident / 100));

        $idealFraction = $resident->idealFraction();
        if ($idealFraction >= 0) {
            if ($sumOfFractionBasedExpensesInCents > 0) {
                $shareOfFractionExpenses = (int)round($sumOfFractionBasedExpensesInCents * $idealFraction);
                $residentSlipAmountInCents += $shareOfFractionExpenses;
                $this->logger->info(sprintf(
                    '    + Despesas por fração ideal para %s (%.2f%% de %.2f): %.2f',
                    $resident->unit(), $idealFraction * 100, $sumOfFractionBasedExpensesInCents / 100, $shareOfFractionExpenses / 100));
            }
        } elseif ($sumOfFractionBasedExpensesInCents > 0) {
            $this->logger->warning(sprintf(
                '    ! Residente %s (ID: %s) no tiene fracción ideal definida, pero existen gastos (%.2f) que se distribuyen por fracción. Su parte será 0.',
                $resident->unit(), $resident->id(), $sumOfFractionBasedExpensesInCents / 100
            ));
        }

        return $residentSlipAmountInCents;
    }

    private function fifthBussinessDayOfMonth(\DateTimeInterface $targetMonthDate): \DateTimeInterface
    {
        $firstDayOfNextMonth = $targetMonthDate
            ->modify('first day of next month')
            ->setTime(0, 0, 0);

        $businessDaysCount = 0;
        $currentDay = $firstDayOfNextMonth;

        while ($businessDaysCount < 5) {
            $dayOfWeek = (int)$currentDay->format('N');
            if ($dayOfWeek >= 1 && $dayOfWeek <= 5) {
                $businessDaysCount++;
            }

            if ($businessDaysCount < 5) {
                $currentDay = $currentDay->modify('+1 day');
            }
        }

        return $currentDay;
    }
}

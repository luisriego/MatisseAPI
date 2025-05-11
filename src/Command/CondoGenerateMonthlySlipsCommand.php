<?php

namespace App\Command;

use App\Entity\Expense;
use App\Entity\Income; // Asegúrate de importar Income
use App\Entity\Resident;
use App\Entity\Slip;
use App\Repository\ExpenseRepository;
use App\Repository\IncomeRepository; // Inyectar IncomeRepository
use App\Repository\ResidentRepository;
use App\Repository\SlipRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Clock\ClockInterface;

#[AsCommand(
    name: 'app:condo:generate-monthly-slips',
    description: 'Generate the mensual slips for condo residents.',
)]
final class CondoGenerateMonthlySlipsCommand extends Command
{
    private const string GAS_RESTITUTION_INCOME_TYPE_CODE = 'GAS_REST';

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ExpenseRepository      $expenseRepository,
        private readonly ResidentRepository     $residentRepository,
        private readonly SlipRepository         $slipRepository,
        private readonly IncomeRepository       $incomeRepository,
        private readonly ClockInterface         $clock
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'month',
                'm',
                InputOption::VALUE_OPTIONAL,
                'El mes a procesar (YYYY-MM). Por defecto, el mes actual si el cron corre a fin de mes.'
            )
            ->addOption(
                'force',
                null,
                InputOption::VALUE_NONE,
                'Forzar la generación incluso si ya existen boletos para el mes.'
            );
    }

    /**
     * @throws \DateMalformedStringException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // 1. Determinar el mes a procesar
        $monthOption = $input->getOption('month');
        if ($monthOption) {
            try {
                $targetDate = new \DateTimeImmutable($monthOption . '-01');
            } catch (\Exception $e) {
                $io->error('Formato de mes inválido. Usa YYYY-MM.');
                return Command::FAILURE;
            }
        } else {
            $targetDate = $this->clock->now()->modify('first day of this month');
        }

        $year = $targetDate->format('Y');
        $month = $targetDate->format('m');
        $monthName = $targetDate->format('F Y');

        $io->title("Generando boletos condominiales para: " . $monthName);

        $startDate = $targetDate;
        $endDate = $targetDate->modify('last day of this month')->setTime(23, 50, 00);

        if (!$input->getOption('force')) {
            $existingSlips = $this->slipRepository->countForMonth((int)$year, (int)$month);
            if ($existingSlips > 0) {
                $io->warning(sprintf('Ya existen %d boletos para %s. Usa --force para regenerar.', $existingSlips, $monthName));
                return Command::SUCCESS;
            }
        }

        $monthlyExpenses = $this->expenseRepository->findExpensesBetweenDates($startDate, $endDate);

        $totalExpensesSumForLogging = 0;
        foreach ($monthlyExpenses as $expense) {
            $totalExpensesSumForLogging += $expense->amount();
        }
        $io->writeln(sprintf('Suma total de gastos registrados en %s: %.2f', $monthName, $totalExpensesSumForLogging / 100));

        $missingRecurringExpenses = $this->checkRecurringExpenses($monthlyExpenses, $year, $month);
        if (!empty($missingRecurringExpenses)) {
            $io->warning('Faltan los siguientes gastos recurrentes esperados:');
            foreach ($missingRecurringExpenses as $missing) {
                $io->writeln(' - ' . $missing);
            }
        }


        $activeResidents = $this->residentRepository->findAllActive();
        if (empty($activeResidents)) {
            $io->error('No se encontraron residentes activos para generar boletos.');
            return Command::FAILURE;
        }
        $numberOfActiveResidents = count($activeResidents);
        if ($numberOfActiveResidents < 5) {
            $io->error('The number of residents must 5, no more, no less.');
        }
        $io->writeln(sprintf('Slips for %d residents will be calculated.', $numberOfActiveResidents));

        $slipsGenerated = 0;
        $this->entityManager->beginTransaction();
        try {
            foreach ($activeResidents as $resident) {
                // Calcular el monto base del boleto a partir de los gastos comunes y cuotas fijas
                $baseSlipAmountInCents = $this->calculateBaseSlipAmountForExpenses(
                    $resident,
                    $monthlyExpenses,
                    $numberOfActiveResidents,
                    $io
                );

                // Obtener y sumar los Incomes de restitución de gas para este residente y mes
                $gasRestitutionIncomes = $this->incomeRepository->findIncomesForResidentByTypeCodeAndDateRange(
                    $resident,
                    self::GAS_RESTITUTION_INCOME_TYPE_CODE, // Necesitarás este método en IncomeRepository
                    $startDate,
                    $endDate
                );

                $totalGasRestitutionInCents = 0;
                foreach ($gasRestitutionIncomes as $income) {
                    $totalGasRestitutionInCents += $income->amount();
                }

                if ($totalGasRestitutionInCents > 0) {
                    $io->writeln(sprintf('    + Restitución de Gas para %s: %.2f', $resident->getName(), $totalGasRestitutionInCents / 100));
                }

                // Monto final del boleto
                $finalSlipAmountInCents = $baseSlipAmountInCents + $totalGasRestitutionInCents;


                if ($finalSlipAmountInCents <= 0) {
                    // Solo omitir si es realmente cero Y no hay gastos base ni restitución de gas.
                    // Si hay una restitución de gas pero los gastos base son negativos (crédito), podría ser válido.
                    // Por ahora, una lógica simple:
                    if ($baseSlipAmountInCents <= 0 && $totalGasRestitutionInCents <= 0) {
                        $io->writeln(sprintf('Omitiendo boleto para %s (monto calculado es cero o negativo sin componentes significativos).', $resident->getName()));
                        continue;
                    }
                }

                $dueDate = $this->clock->now()->modify('first day of next month')->modify('+9 days');

                $slip = Slip::create(
                    $resident,
                    $finalSlipAmountInCents,
                    $dueDate,
                    sprintf('Boleto Condominial %s', $monthName),
                    (int)$year,
                    (int)$month
                );

                $this->entityManager->persist($slip);
                $slipsGenerated++;
                $io->writeln(sprintf('Boleto generado para %s (ID: %s) - Monto Final: %.2f', $resident->getName(), $resident->id(), $finalSlipAmountInCents / 100));
            }

            // ... (commit/rollback y mensajes de éxito/nota sin cambios) ...
            if ($slipsGenerated > 0) {
                $this->entityManager->flush();
                $this->entityManager->commit();
                $io->success(sprintf('Se generaron y guardaron %d boletos condominiales para %s.', $slipsGenerated, $monthName));
            } else {
                $this->entityManager->rollback();
                $io->note(sprintf('No se generaron nuevos boletos para %s.', $monthName));
            }

        } catch (\Exception $e) {
            $this->entityManager->rollback();
            $io->error('Error durante la generación de boletos: ' . $e->getMessage());
            $io->writeln('Trace: ' . $e->getTraceAsString());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    /**
     * Lógica para verificar gastos recurrentes.
     */
    private function checkRecurringExpenses(array $monthlyExpenses, string $year, string $month): array
    {
        // ... (sin cambios) ...
        $missing = [];
        $expectedRecurringDescriptions = [ /* 'Agua Común', 'Luz Pasillos' */ ];
        $foundExpenseDescriptions = [];
        foreach ($monthlyExpenses as $expense) {
            if ($expense->type()) { // Cambiado de getExpenseType a type() para coincidir con Expense.php
                // $foundExpenseDescriptions[] = $expense->type()->name(); // Cambiado de getName a name()
            }
        }
        foreach ($expectedRecurringDescriptions as $expected) {
            if (!in_array($expected, $foundExpenseDescriptions, true)) {
                $missing[] = $expected;
            }
        }
        return $missing;
    }

    /**
     * Calcula el monto BASE del boleto a partir de los GASTOS comunes y cuotas fijas.
     * Los ingresos individuales (como restitución de gas) se añaden por separado.
     */
    private function calculateBaseSlipAmountForExpenses(
        Resident $resident,
        array $monthlyExpenses,
        int $numberOfActiveResidents,
        SymfonyStyle $io
    ): int {
        $residentSlipAmountInCents = 0;
        $sumOfEquallyDividedExpensesInCents = 0;
        $sumOfFractionBasedExpensesInCents = 0;

        foreach ($monthlyExpenses as $expense) {
            $expenseType = $expense->type();
            if (!$expenseType) {
                $io->warning(sprintf('Gasto ID %s (Monto: %.2f) no tiene un tipo de gasto asignado. Se omitirá del cálculo detallado.', $expense->id(), $expense->amount()/100));
                continue;
            }

            // ASUNCIÓN: ExpenseType tiene distributionMethod() -> 'EQUAL', 'FRACTION'
            $distributionMethod = $expenseType->distributionMethod(); // Cambiado de getDistributionMethod a distributionMethod()

            switch (strtoupper((string)$distributionMethod)) {
                case 'EQUAL':
                    $sumOfEquallyDividedExpensesInCents += $expense->amount();
                    break;
                case 'FRACTION':
                    $sumOfFractionBasedExpensesInCents += $expense->amount();
                    break;
                // El caso 'INDIVIDUAL' para Expense se elimina de aquí, ya que el gas se maneja como Income.
                // Si tienes OTROS gastos que son verdaderamente individuales y son Expenses,
                // necesitarías una forma de vincular Expense con Resident y reintroducir este caso.
                default:
                    $io->writeln(sprintf(
                        '  - Gasto "%s" (Tipo: "%s", Monto: %.2f) tiene un método de distribución "%s" no aplicable para cálculo base de gastos comunes. Se omitirá.',
                        $expense->description() ?? $expense->id(),
                        $expenseType->name(), // Cambiado de getName a name()
                        $expense->amount()/100,
                        $distributionMethod ?? 'N/A'
                    ));
                    break;
            }
        }

        // --- Calcular parte de gastos divididos por igual ---
        if ($numberOfActiveResidents > 0 && $sumOfEquallyDividedExpensesInCents > 0) {
            $amountPerResident = (int) round($sumOfEquallyDividedExpensesInCents / $numberOfActiveResidents);
            $residentSlipAmountInCents += $amountPerResident;
            $io->writeln(sprintf('    + Gastos por igual para %s: %.2f', $resident->getName(), $amountPerResident/100));
        }

        // --- Calcular parte de gastos divididos por fracción ---
        $idealFraction = $resident->idealFraction(); // Cambiado de getIdealFraction a idealFraction()
        if ($idealFraction !== null && $idealFraction >= 0) {
            if ($sumOfFractionBasedExpensesInCents > 0) {
                $shareOfFractionExpenses = (int) round($sumOfFractionBasedExpensesInCents * $idealFraction);
                $residentSlipAmountInCents += $shareOfFractionExpenses;
                $io->writeln(sprintf('    + Gastos por fracción para %s (%.2f%% de %.2f): %.2f', $resident->getName(), $idealFraction*100, $sumOfFractionBasedExpensesInCents/100, $shareOfFractionExpenses/100));
            }
        } elseif ($sumOfFractionBasedExpensesInCents > 0) {
            $io->warning(sprintf(
                '    ! Residente %s (ID: %s) no tiene fracción ideal definida, pero existen gastos (%.2f) que se distribuyen por fracción. Su parte de estos gastos será 0.',
                $resident->getName(), $resident->id(), $sumOfFractionBasedExpensesInCents / 100
            ));
        }

        // --- Añadir Cuotas Fijas (ej. fondos de reserva, obras) ---
        $reserveFundAmountPerResidentInCents = 5000; // Ejemplo: 50.00
        $residentSlipAmountInCents += $reserveFundAmountPerResidentInCents;
        $io->writeln(sprintf('    + Fondo de Reserva para %s: %.2f', $resident->getName(), $reserveFundAmountPerResidentInCents/100));

        $worksFundAmountPerResidentInCents = 2000; // Ejemplo: 20.00
        $residentSlipAmountInCents += $worksFundAmountPerResidentInCents;
        $io->writeln(sprintf('    + Fondo de Obras para %s: %.2f', $resident->getName(), $worksFundAmountPerResidentInCents/100));

        $io->writeln(sprintf('    = Monto base de gastos y cuotas fijas para %s: %.2f', $resident->getName(), $residentSlipAmountInCents/100));

        return $residentSlipAmountInCents;
    }
}

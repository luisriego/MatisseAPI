<?php

declare(strict_types=1);

namespace App\Service;

// Ya no necesita App\Entity\Expense directamente
use App\Entity\Resident;
use Psr\Log\LoggerInterface;

readonly class SlipAmountCalculatorService
{
    public function __construct(private LoggerInterface $logger)
    {
    }

    /**
     * Calcula el monto base del boleto para un residente específico basado
     * en los totales de gastos pre-agregados.
     *
     * @param Resident $resident El residente para el cual calcular el monto.
     * @param int $totalEquallyDividedExpensesInCents Suma total de gastos a dividir equitativamente.
     * @param int $totalFractionBasedExpensesInCents Suma total de gastos a dividir por fracción.
     * @param int $numberOfPayingResidents El número total de residentes que pagan.
     * @return int El monto del boleto en centavos.
     */
    public function calculateBaseAmount(
        Resident $resident,
        int $totalEquallyDividedExpensesInCents, // <--- NUEVO PARÁMETRO
        int $totalFractionBasedExpensesInCents,  // <--- NUEVO PARÁMETRO
        int $numberOfPayingResidents
    ): int {
        $residentSlipAmountInCents = 0;

        // La suma de gastos ya viene calculada, no se necesita el bucle foreach aquí.

        if ($numberOfPayingResidents > 0 && $totalEquallyDividedExpensesInCents > 0) {
            $amountPerResidentEqual = (int)round($totalEquallyDividedExpensesInCents / $numberOfPayingResidents);
            $residentSlipAmountInCents += $amountPerResidentEqual;
            $this->logger->info(sprintf(
                '[SlipAmountCalculator]    + (Residente: %s) Despesas igualmente divididas: %.2f (Total Global: %.2f / %d residentes)',
                $resident->unit(),
                $amountPerResidentEqual / 100,
                $totalEquallyDividedExpensesInCents / 100,
                $numberOfPayingResidents
            ));
        } elseif ($totalEquallyDividedExpensesInCents > 0) { // Si hay gastos pero no residentes para dividir
            $this->logger->warning(sprintf(
                '[SlipAmountCalculator]    ! (Residente: %s) Existem despesas de divisão igual (%.2f) mas o número de residentes pagadores é zero ou não foi fornecido. A parte do residente para estas despesas será 0.',
                $resident->unit(),
                $totalEquallyDividedExpensesInCents / 100
            ));
        }


        $idealFraction = $resident->idealFraction();

        if ($idealFraction > 0 && $totalFractionBasedExpensesInCents > 0) {
            $shareOfFractionExpenses = (int)round($totalFractionBasedExpensesInCents * $idealFraction);
            $residentSlipAmountInCents += $shareOfFractionExpenses;
            $this->logger->info(sprintf(
                '[SlipAmountCalculator]    + (Residente: %s) Despesas por fração ideal (%.2f%% de Total Global %.2f): %.2f',
                $resident->unit(),
                $idealFraction * 100,
                $totalFractionBasedExpensesInCents / 100,
                $shareOfFractionExpenses / 100
            ));
        } elseif ($totalFractionBasedExpensesInCents > 0 && $idealFraction <= 0) {
            $this->logger->warning(sprintf(
                '[SlipAmountCalculator]    ! (Residente: %s) Não tem fração ideal definida ou é zero, mas existem gastos (Total Global %.2f) que se distribuem por fração. Sua parte será 0 para estes gastos.',
                $resident->unit(),
                $totalFractionBasedExpensesInCents / 100
            ));
        }

        return $residentSlipAmountInCents;
    }
}

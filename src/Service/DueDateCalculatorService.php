<?php

declare(strict_types=1);

namespace App\Service;

use Psr\Log\LoggerInterface;

readonly class DueDateCalculatorService
{
    public function __construct(private LoggerInterface $logger)
    {
    }

    /**
     * Calcula el quinto día hábil del mes de la fecha proporcionada.
     *
     * @param \DateTimeInterface $targetMonthDate Una fecha dentro del mes para el cual calcular el 5º día hábil.
     * @return \DateTimeInterface El quinto día hábil del mes.
     */
    public function fifthBusinessDayOfMonth(\DateTimeInterface $targetMonthDate): \DateTimeInterface
    {
        $currentDay = \DateTime::createFromInterface($targetMonthDate)
            ->modify('first day of this month')
            ->setTime(0, 0, 0);

        $businessDaysCount = 0;
        $daysChecked = 0; // Para evitar bucles infinitos

        while ($businessDaysCount < 5 && $daysChecked < 31) {
            $dayOfWeek = (int)$currentDay->format('N'); // 1 (Mon) to 7 (Sun)
            if ($dayOfWeek >= 1 && $dayOfWeek <= 5) { // Es un día de semana
                $businessDaysCount++;
            }
            // Avanzar al siguiente día solo si aún no hemos encontrado los 5 días hábiles
            if ($businessDaysCount < 5) {
                $currentDay = $currentDay->modify('+1 day');
            }
            $daysChecked++;
        }

        if ($businessDaysCount < 5) {
            // Esto no debería ocurrir en un mes normal, pero es un fallback
            $this->logger->warning(sprintf(
                "[DueDateCalculator] Não foi possível determinar o 5º dia útil para %s. Usando o 5º dia corrido do mês seguinte como fallback.",
                $targetMonthDate->format('Y-m')
            ));
            // Devuelve el 5º día calendario del mes siguiente como fallback.
            return \DateTime::createFromInterface($targetMonthDate)
                ->modify('first day of next month')
                ->modify('+4 days');
        }

        return $currentDay;
    }

    // Podrías añadir otros métodos de cálculo de fechas aquí en el futuro, por ejemplo:
    // public function lastBusinessDayOfMonth(\DateTimeInterface $targetMonthDate): \DateTimeInterface { ... }
    // public function specificDayOrNextBusinessDay(int $day, \DateTimeInterface $targetMonthDate): \DateTimeInterface { ... }
}

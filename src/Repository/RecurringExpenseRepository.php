<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Repository;

use App\Entity\RecurringExpense;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<RecurringExpense>
 */
class RecurringExpenseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RecurringExpense::class);
    }

    public function save(RecurringExpense $recurringExpense, bool $flush): void
    {
        $this->getEntityManager()->persist($recurringExpense);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Encuentra los gastos recurrentes activos para un mes específico.
     * Asume que la entidad RecurringExpense tiene un campo llamado 'monthsOfYear'
     * de tipo 'simple_array' que almacena los números de los meses (ej: "1,2,12").
     *
     * @param int $month El número del mes (ej: 1, 12).
     * @return RecurringExpense[]
     */
    /**
     * Encuentra los gastos recurrentes activos para un mes específico
     * basándose en la frecuencia y el array monthsOfYear.
     *
     * Lógica:
     * - Si la frecuencia es MONTHLY, se incluye para cualquier mes.
     * - Si la frecuencia NO es MONTHLY, se incluye solo si el mes
     *   específico está presente en el array monthsOfYear.
     *
     * @param int $month El número del mes (ej: 1 para enero, 12 para diciembre).
     * @return RecurringExpense[]
     */
    public function findActivesForThisMonth(int $month): array
    {
        // 1. Obtener TODOS los gastos recurrentes que están marcados como activos
        $allActiveRecurring = $this->findAllActives();

        $activeForThisMonth = [];

        // 2. Filtrar los resultados usando lógica PHP
        foreach ($allActiveRecurring as $recurringExpense) {
            $frequency = $recurringExpense->frequency();
            $monthsOfYear = $recurringExpense->monthsOfYear();

            // Lógica de inclusión:
            // - Si es mensual, siempre se incluye.
            // - Si NO es mensual, se incluye SOLO si el array monthsOfYear no es null
            //   y contiene el número del mes actual.
            if (
                $frequency === RecurringExpense::FREQUENCY_MONTHLY ||
                ($monthsOfYear !== null && in_array($month, $monthsOfYear))
            ) {
                $activeForThisMonth[] = $recurringExpense;
            }
        }

        return $activeForThisMonth;
    }

    public function findAllActives(): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.isActive = :isActive')
            ->setParameter('isActive', true)
            ->orderBy('r.dueDay', 'ASC')
            ->getQuery()
            ->getResult();
    }
}

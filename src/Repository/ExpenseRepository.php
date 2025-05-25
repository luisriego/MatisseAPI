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

use App\Entity\Expense;
use App\Entity\RecurringExpense;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Expense>
 */
class ExpenseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Expense::class);
    }

    public function save(Expense $expense, bool $flush):void
    {
        $this->getEntityManager()->persist($expense);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findByMonth(string $month, string $year): array
    {
        $startDate = new \DateTime("$year-$month-01");
        $endDate = (clone $startDate)->modify('last day of this month');

        return $this->createQueryBuilder('e')
            ->where('e.dueDate BETWEEN :startDate AND :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('e.dueDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findGroupedByMonth(): array
    {
        $qb = $this->createQueryBuilder('e')
            ->select('e')
            ->orderBy('e.dueDate', 'ASC');

        $result = $qb->getQuery()->getResult();

        $grouped = [];
        foreach ($result as $expense) {
            $month = $expense->dueDate()->format('Y-m'); // Format: YYYY-MM
            $grouped[$month][] = $expense;
        }

        return $grouped;
    }

    /**
     * @param \DateTimeInterface $startDate Start date of the range.
     * @param \DateTimeInterface $endDate Finish date of the range.
     * @return Expense[]
     */
    public function findExpensesBetweenDates(
        \DateTimeInterface $startDate,
        \DateTimeInterface $endDate
    ): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.dueDate BETWEEN :startDate AND :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('e.dueDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findRecurringExpenses(int $month): array
    {
        $allRecurringExpenses = $this->createQueryBuilder('e')
            ->where('e.isRecurring = :isRecurring')
            ->setParameter('isRecurring', true)
            ->getQuery()
            ->getResult();

        $expensesForGivenMonth = [];
        foreach ($allRecurringExpenses as $expense) {
            if (in_array($month, $expense->getPayOnMonths(), true)) {
                $expensesForGivenMonth[] = $expense;
            }
        }

        return $expensesForGivenMonth;
    }

    public function hasInstanceForRecurringExpenseAndMonth(RecurringExpense $recurringExpense, int $year, int $month): bool
    {
        $monthStr = str_pad((string)$month, 2, '0', STR_PAD_LEFT);
        try {
            // Define the date range for the target month
            $startDate = new \DateTimeImmutable("$year-$monthStr-01 00:00:00");
            $endDate = $startDate->modify('last day of this month')->setTime(23, 59, 59);
        } catch (\Exception $e) {
            // Handle invalid date creation, though with int year/month it should be rare
            // You might want to log this error or re-throw a custom exception
            throw new \InvalidArgumentException("Invalid year or month provided for date range creation.", 0, $e);
        }

        $qb = $this->createQueryBuilder('e');
        $count = $qb->select('COUNT(e.id)')
            ->where('e.recurringExpense = :recurringExpense') // Check link to the RecurringExpense
            ->andWhere('e.dueDate >= :startDate')         // Check if dueDate is within the month
            ->andWhere('e.dueDate <= :endDate')
            ->setParameter('recurringExpense', $recurringExpense)
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->getQuery()
            ->getSingleScalarResult();

        return (int)$count > 0;
    }

    /**
     * Encontra despesas cujo dueDate está dentro do mês e ano fornecidos.
     * @return Expense[]
     */
    public function findByMonthDueDateRange(\DateTimeInterface $targetMonthDate): array
    {
        $startDate = \DateTimeImmutable::createFromInterface($targetMonthDate)
            ->modify('first day of this month')
            ->setTime(0, 0, 0);

        $endDate = \DateTimeImmutable::createFromInterface($targetMonthDate)
            ->modify('last day of this month')
            ->setTime(23, 59, 59);

        return $this->createQueryBuilder('e')
            ->andWhere('e.dueDate >= :startDate')
            ->andWhere('e.dueDate <= :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('e.dueDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function getTotalAmountForCurrentMonth(): int
    {
        $startDate = new \DateTimeImmutable('first day of this month 00:00:00');
        $endDate = new \DateTimeImmutable('last day of this month 23:59:59');

        return (int) $this->createQueryBuilder('e')
            ->select('SUM(e.amount)')
            ->where('e.dueDate BETWEEN :startDate AND :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->getQuery()
            ->getSingleScalarResult() ?? 0;
    }
}

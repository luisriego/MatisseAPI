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
}

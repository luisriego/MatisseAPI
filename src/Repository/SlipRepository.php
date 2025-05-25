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

use App\Entity\Slip;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Slip>
 */
class SlipRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Slip::class);
    }

    /**
     * Finds slips for a given month and year.
     *
     * @param int $month The month (1-12)
     * @param int $year  The year (e.g., 2023)
     * @return Slip[] Returns an array of Slip objects
     */
    public function findByMonthAndYear(int $month, int $year): array
    {
        // Create DateTimeImmutable objects for the start and end of the month
        // Ensures the query covers the entire month.
        try {
            $startDate = new \DateTimeImmutable(sprintf('%d-%02d-01 00:00:00', $year, $month));
            $endDate = $startDate->modify('last day of this month')->setTime(23, 59, 59);
        } catch (\Exception $e) {
            // Handle invalid date inputs, though $month and $year should be validated upstream
            // or this could throw an exception. For now, return empty array on error.
            return [];
        }

        return $this->createQueryBuilder('s')
            // Assuming your Slip entity has a 'dueDate' field.
            // Adjust 's.dueDate' if your date field is named differently.
            ->andWhere('s.dueDate BETWEEN :startDate AND :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('s.dueDate', 'ASC') // Optional: order the results
            ->getQuery()
            ->getResult();
    }

    /**
     * @throws \DateMalformedStringException
     */
    public function countForMonth(int $year, int $month): int
    {
        $startDate = new \DateTimeImmutable(sprintf('%04d-%02d-01 00:00:00', $year, $month));
        $endDate = $startDate->modify('last day of this month')->setTime(23, 59, 59);

        return (int) $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->where('s.dueDate >= :startDate')
            ->andWhere('s.dueDate <= :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->getQuery()
            ->getSingleScalarResult();
    }
}

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

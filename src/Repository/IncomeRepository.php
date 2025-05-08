<?php

namespace App\Repository;

use App\Entity\Income;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Income>
 */
class IncomeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Income::class);
    }

    public function save(Income $income, bool $flush = false): void
    {
        $this->getEntityManager()->persist($income);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findOneByIdOrFail(string $id): Income
    {
        $income = $this->find($id);

        if (!$income) {
            throw new \Doctrine\ORM\EntityNotFoundException("Income with ID $id not found.");
        }

        return $income;
    }
}

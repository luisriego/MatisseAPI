<?php

namespace App\Repository;

use App\Entity\Income;
use App\Entity\Resident;
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

    /**
     * @param Resident $resident
     * @param string $typeCode
     * @param \DateTimeInterface $startDate
     * @param \DateTimeInterface $endDate
     * @return Income[]
     */
    public function findIncomesForResidentByTypeCodeAndDateRange(
        Resident $resident,
        string $typeCode,
        \DateTimeInterface $startDate,
        \DateTimeInterface $endDate
    ): array {
        return $this->createQueryBuilder('i')
            ->innerJoin('i.type', 'it') // 'type' is the relation in Income to IncomeType
            ->where('i.residence = :residentId') // 'residence' is the relation in Income to Resident
            ->andWhere('it.code = :typeCode')      // 'code' is the field in IncomeType
            ->andWhere('i.createdAt >= :startDate') // 'createdAt' is the date field in Income
            ->andWhere('i.createdAt <= :endDate')
            ->setParameter('residentId', $resident->id())
            ->setParameter('typeCode', $typeCode)
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('i.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }
}

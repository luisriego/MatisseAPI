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

use App\Entity\IncomeType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<IncomeType>
 */
class IncomeTypeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, IncomeType::class);
    }

    public function save(IncomeType $incomeType, bool $flush): void
    {
        $this->getEntityManager()->persist($incomeType);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findOneByIdOrFail(int $id): IncomeType
    {
        $incomeType = $this->findOneBy(['id' => $id]);

        if (!$incomeType) {
            throw new \Doctrine\ORM\EntityNotFoundException("IncomeType with ID $id not found.");
        }

        return $incomeType;
    }
}

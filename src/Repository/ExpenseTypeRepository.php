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

use App\Entity\ExpenseType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ExpenseTypeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ExpenseType::class);
    }

    public function save(ExpenseType $expenseType, bool $flush): void
    {
        $this->getEntityManager()->persist($expenseType);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findOneByIdOrFail(string $id): ExpenseType
    {
        if (null === $expenseType = $this->findOneBy(['id' => $id])) {
            throw new \InvalidArgumentException(sprintf('The expense type with id "%s" does not exist.', $id));
        }

        return $expenseType;
    }
}

<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Resident;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ResidentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Resident::class);
    }

    public function save(Resident $resident, bool $flush): void
    {
        $this->getEntityManager()->persist($resident);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findOneByIdOrFail(string $id): Resident
    {
        if (null === $resident = $this->findOneBy(['id' => $id])) {
            throw new \InvalidArgumentException(sprintf('The resident with id "%s" does not exist.', $id));
        }

        return $resident;
    }

    public function findAllPayers(): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.unit <> :condoUnit')
            ->setParameter('condoUnit', Resident::CONDO)
            ->orderBy('r.unit', 'ASC')
            ->getQuery()
            ->getResult();
    }
}

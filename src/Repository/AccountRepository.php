<?php

namespace App\Repository;

use App\Entity\Account;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class AccountRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Account::class);
    }

    public function save(Account $account, bool $flush): void
    {
        $this->getEntityManager()->persist($account);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findOneByIdOrFail(string $paidFromAccountId): Account
    {
        if (null === $account = $this->findOneBy(['id' => $paidFromAccountId])) {
            throw new \InvalidArgumentException(sprintf('The account with id "%s" does not exist.', $paidFromAccountId));
        }

        return $account;
    }
}
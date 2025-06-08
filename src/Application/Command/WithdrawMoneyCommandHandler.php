<?php

declare(strict_types=1);

namespace App\Application\Command;

use App\Application\Port\Out\AccountRepositoryInterface;
use DomainException; // Assuming DomainException is used for "not found" or by the entity

final class WithdrawMoneyCommandHandler
{
    private AccountRepositoryInterface $accountRepository;

    public function __construct(AccountRepositoryInterface $accountRepository)
    {
        $this->accountRepository = $accountRepository;
    }

    public function handle(WithdrawMoneyCommand $command): void
    {
        $account = $this->accountRepository->findById($command->getAccountId());

        if (null === $account) {
            throw new DomainException('Account not found.'); // Or a more specific exception
        }

        try {
            $account->withdraw($command->getAmount()); // Domain entity handles insufficient funds
        } finally {
            // Always save to ensure events (like failure events) are persisted
            // In a real system with transactions, this would be part of the UoW commit/rollback logic.
            $this->accountRepository->save($account);
        }
    }
}

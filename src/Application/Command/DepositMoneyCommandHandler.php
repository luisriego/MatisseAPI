<?php

declare(strict_types=1);

namespace App\Application\Command;

use App\Application\Port\Out\AccountRepositoryInterface;
use DomainException; // Assuming DomainException is used for "not found"

final class DepositMoneyCommandHandler
{
    private AccountRepositoryInterface $accountRepository;

    public function __construct(AccountRepositoryInterface $accountRepository)
    {
        $this->accountRepository = $accountRepository;
    }

    public function handle(DepositMoneyCommand $command): void
    {
        $account = $this->accountRepository->findById($command->getAccountId());

        if (null === $account) {
            throw new DomainException('Account not found.'); // Or a more specific exception
        }

        $account->deposit($command->getAmount());
        $this->accountRepository->save($account);
    }
}

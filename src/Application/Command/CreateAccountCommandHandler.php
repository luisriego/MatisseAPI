<?php

declare(strict_types=1);

namespace App\Application\Command;

use App\Application\Port\Out\AccountRepositoryInterface;
use App\Domain\Entity\Account;
use App\Domain\ValueObject\AccountId;

final class CreateAccountCommandHandler
{
    private AccountRepositoryInterface $accountRepository;

    public function __construct(AccountRepositoryInterface $accountRepository)
    {
        $this->accountRepository = $accountRepository;
    }

    public function handle(CreateAccountCommand $command): AccountId
    {
        $accountId = AccountId::generate();
        $account = new Account(
            $accountId,
            $command->getCustomerId(),
            $command->getInitialBalance()
        );

        $this->accountRepository->save($account);

        return $accountId;
    }
}

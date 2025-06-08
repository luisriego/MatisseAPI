<?php

declare(strict_types=1);

namespace App\Application\Query;

use App\Application\DTO\AccountBalanceDTO;
use App\Application\Port\Out\AccountRepositoryInterface;
use DomainException; // Assuming DomainException is used for "not found"

final class GetAccountBalanceQueryHandler
{
    private AccountRepositoryInterface $accountRepository;

    public function __construct(AccountRepositoryInterface $accountRepository)
    {
        $this->accountRepository = $accountRepository;
    }

    public function handle(GetAccountBalanceQuery $query): AccountBalanceDTO
    {
        $account = $this->accountRepository->findById($query->getAccountId());

        if (null === $account) {
            throw new DomainException('Account not found.'); // Or a more specific exception
        }

        $balance = $account->getBalance();

        return new AccountBalanceDTO(
            $account->getId()->toString(),
            $balance->getAmount(),
            $balance->getCurrency()->getCode()
        );
    }
}

<?php

declare(strict_types=1);

namespace App\Application\DTO;

final class AccountTransactionsDTO
{
    public string $accountId;
    /** @var TransactionDTO[] */
    public array $transactions;

    /**
     * @param string $accountId
     * @param TransactionDTO[] $transactions
     */
    public function __construct(string $accountId, array $transactions)
    {
        $this->accountId = $accountId;
        $this->transactions = $transactions;
    }
}

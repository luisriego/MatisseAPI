<?php

declare(strict_types=1);

namespace App\Application\Query;

use App\Domain\ValueObject\AccountId;

final class GetAccountTransactionsQuery
{
    private AccountId $accountId;

    public function __construct(AccountId $accountId)
    {
        $this->accountId = $accountId;
    }

    public function getAccountId(): AccountId
    {
        return $this->accountId;
    }
}

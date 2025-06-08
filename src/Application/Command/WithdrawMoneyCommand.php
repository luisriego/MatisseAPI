<?php

declare(strict_types=1);

namespace App\Application\Command;

use App\Domain\ValueObject\AccountId;
use App\Domain\ValueObject\Money;

final class WithdrawMoneyCommand
{
    private AccountId $accountId;
    private Money $amount;

    public function __construct(AccountId $accountId, Money $amount)
    {
        $this->accountId = $accountId;
        $this->amount = $amount;
    }

    public function getAccountId(): AccountId
    {
        return $this->accountId;
    }

    public function getAmount(): Money
    {
        return $this->amount;
    }
}

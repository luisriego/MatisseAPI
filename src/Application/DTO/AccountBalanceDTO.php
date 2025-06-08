<?php

declare(strict_types=1);

namespace App\Application\DTO;

final class AccountBalanceDTO
{
    public string $accountId;
    public int $balanceAmount; // In cents
    public string $currencyCode;

    public function __construct(string $accountId, int $balanceAmount, string $currencyCode)
    {
        $this->accountId = $accountId;
        $this->balanceAmount = $balanceAmount;
        $this->currencyCode = $currencyCode;
    }
}

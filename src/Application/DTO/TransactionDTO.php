<?php

declare(strict_types=1);

namespace App\Application\DTO;

final class TransactionDTO
{
    public string $transactionId;
    public string $accountId;
    public int $amount; // In cents
    public string $currencyCode;
    public string $type;
    public string $createdAt;

    public function __construct(
        string $transactionId,
        string $accountId,
        int $amount,
        string $currencyCode,
        string $type,
        string $createdAt
    ) {
        $this->transactionId = $transactionId;
        $this->accountId = $accountId;
        $this->amount = $amount;
        $this->currencyCode = $currencyCode;
        $this->type = $type;
        $this->createdAt = $createdAt;
    }
}

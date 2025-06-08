<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\Enum\TransactionType;
use App\Domain\ValueObject\AccountId;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\TransactionId;
use DateTimeImmutable;

final class Transaction
{
    private TransactionId $id;
    private AccountId $accountId;
    private Money $amount;
    private TransactionType $type;
    private DateTimeImmutable $createdAt;

    public function __construct(
        TransactionId $id,
        AccountId $accountId,
        Money $amount,
        TransactionType $type,
        DateTimeImmutable $createdAt
    ) {
        $this->id = $id;
        $this->accountId = $accountId;
        $this->amount = $amount;
        $this->type = $type;
        $this->createdAt = $createdAt;
    }

    public function getId(): TransactionId
    {
        return $this->id;
    }

    public function getAccountId(): AccountId
    {
        return $this->accountId;
    }

    public function getAmount(): Money
    {
        return $this->amount;
    }

    public function getType(): TransactionType
    {
        return $this->type;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
}

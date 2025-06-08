<?php

declare(strict_types=1);

namespace App\Application\Command;

use App\Domain\ValueObject\CustomerId;
use App\Domain\ValueObject\Money;

final class CreateAccountCommand
{
    private CustomerId $customerId;
    private Money $initialBalance;

    public function __construct(CustomerId $customerId, Money $initialBalance)
    {
        $this->customerId = $customerId;
        $this->initialBalance = $initialBalance;
    }

    public function getCustomerId(): CustomerId
    {
        return $this->customerId;
    }

    public function getInitialBalance(): Money
    {
        return $this->initialBalance;
    }
}

<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

use InvalidArgumentException;

final class Money
{
    private int $amount; // In cents
    private Currency $currency;

    public function __construct(int $amount, Currency $currency)
    {
        $this->amount = $amount;
        $this->currency = $currency;
    }

    public function getAmount(): int
    {
        return $this->amount;
    }

    public function getCurrency(): Currency
    {
        return $this->currency;
    }

    public function add(Money $other): self
    {
        if (!$this->currency->equals($other->currency)) {
            throw new InvalidArgumentException('Cannot add money with different currencies.');
        }
        return new self($this->amount + $other->amount, $this->currency);
    }

    public function subtract(Money $other): self
    {
        if (!$this->currency->equals($other->currency)) {
            throw new InvalidArgumentException('Cannot subtract money with different currencies.');
        }
        return new self($this->amount - $other->amount, $this->currency);
    }

    public function equals(Money $other): bool
    {
        return $this->amount === $other->amount && $this->currency->equals($other->currency);
    }

    public function __toString(): string
    {
        return sprintf('%d %s', $this->amount, $this->currency->getCode());
    }
}

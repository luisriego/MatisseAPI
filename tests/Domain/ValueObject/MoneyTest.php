<?php

declare(strict_types=1);

namespace Tests\Domain\ValueObject;

use App\Domain\ValueObject\Currency;
use App\Domain\ValueObject\Money;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class MoneyTest extends TestCase
{
    public function testCreationAndGetters(): void
    {
        $currency = new Currency('USD');
        $money = new Money(1000, $currency);

        $this->assertSame(1000, $money->getAmount());
        $this->assertSame($currency, $money->getCurrency());
        $this->assertEquals('USD', $money->getCurrency()->getCode());
    }

    public function testAddition(): void
    {
        $usd = new Currency('USD');
        $moneyA = new Money(1000, $usd);
        $moneyB = new Money(500, $usd);

        $result = $moneyA->add($moneyB);

        $this->assertInstanceOf(Money::class, $result);
        $this->assertSame(1500, $result->getAmount());
        $this->assertSame($usd, $result->getCurrency());

        // Test immutability
        $this->assertSame(1000, $moneyA->getAmount());
        $this->assertSame(500, $moneyB->getAmount());
    }

    public function testAdditionWithDifferentCurrenciesThrowsException(): void
    {
        $usd = new Currency('USD');
        $eur = new Currency('EUR');
        $moneyA = new Money(1000, $usd);
        $moneyB = new Money(500, $eur);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot add money with different currencies.');

        $moneyA->add($moneyB);
    }

    public function testSubtraction(): void
    {
        $usd = new Currency('USD');
        $moneyA = new Money(1000, $usd);
        $moneyB = new Money(300, $usd);

        $result = $moneyA->subtract($moneyB);

        $this->assertInstanceOf(Money::class, $result);
        $this->assertSame(700, $result->getAmount());
        $this->assertSame($usd, $result->getCurrency());

        // Test immutability
        $this->assertSame(1000, $moneyA->getAmount());
        $this->assertSame(300, $moneyB->getAmount());
    }

    public function testSubtractionWithDifferentCurrenciesThrowsException(): void
    {
        $usd = new Currency('USD');
        $eur = new Currency('EUR');
        $moneyA = new Money(1000, $usd);
        $moneyB = new Money(500, $eur);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot subtract money with different currencies.');

        $moneyA->subtract($moneyB);
    }

    public function testEquals(): void
    {
        $usd = new Currency('USD');
        $eur = new Currency('EUR');

        $moneyA = new Money(1000, $usd);
        $moneyB = new Money(1000, $usd);
        $moneyC = new Money(1500, $usd);
        $moneyD = new Money(1000, $eur);

        $this->assertTrue($moneyA->equals($moneyB));
        $this->assertFalse($moneyA->equals($moneyC));
        $this->assertFalse($moneyA->equals($moneyD));
    }

    public function testToStringMethod(): void
    {
        $currency = new Currency('EUR');
        $money = new Money(12345, $currency);
        $this->assertEquals('12345 EUR', $money->__toString());
    }
}

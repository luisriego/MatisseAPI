<?php

declare(strict_types=1);

namespace Tests\Domain\Entity;

use App\Domain\Entity\Account;
use App\Domain\Event\AccountCreated;
use App\Domain\Event\MoneyDeposited;
use App\Domain\Event\MoneyWithdrawn;
use App\Domain\Event\WithdrawalFailedDueToInsufficientFunds;
use App\Domain\ValueObject\AccountId;
use App\Domain\ValueObject\Currency;
use App\Domain\ValueObject\CustomerId;
use App\Domain\ValueObject\Money;
use DomainException;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class AccountTest extends TestCase
{
    private AccountId $accountId;
    private CustomerId $customerId;
    private Currency $usd;
    private Money $initialBalance;

    protected function setUp(): void
    {
        $this->accountId = AccountId::generate();
        $this->customerId = CustomerId::generate();
        $this->usd = new Currency('USD');
        $this->initialBalance = new Money(10000, $this->usd); // 100.00 USD
    }

    public function testAccountCreation(): void
    {
        $account = new Account($this->accountId, $this->customerId, $this->initialBalance);

        $this->assertTrue($this->accountId->equals($account->getId()));
        $this->assertTrue($this->customerId->equals($account->getCustomerId()));
        $this->assertTrue($this->initialBalance->equals($account->getBalance()));

        $events = $account->pullDomainEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(AccountCreated::class, $events[0]);

        /** @var AccountCreated $event */
        $event = $events[0];
        $this->assertTrue($this->accountId->equals($event->getAccountId()));
        $this->assertTrue($this->customerId->equals($event->getCustomerId()));
        $this->assertTrue($this->initialBalance->equals($event->getInitialBalance()));
    }

    public function testDepositMoney(): void
    {
        $account = new Account($this->accountId, $this->customerId, $this->initialBalance);
        $account->pullDomainEvents(); // Clear initial creation event

        $depositAmount = new Money(5000, $this->usd); // 50.00 USD
        $account->deposit($depositAmount);

        $expectedBalance = new Money(15000, $this->usd); // 150.00 USD
        $this->assertTrue($expectedBalance->equals($account->getBalance()));

        $events = $account->pullDomainEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(MoneyDeposited::class, $events[0]);

        /** @var MoneyDeposited $event */
        $event = $events[0];
        $this->assertTrue($this->accountId->equals($event->getAccountId()));
        $this->assertTrue($depositAmount->equals($event->getAmountDeposited()));
        $this->assertTrue($expectedBalance->equals($event->getNewBalance()));
    }

    public function testDepositMoneyWithDifferentCurrencyThrowsException(): void
    {
        $account = new Account($this->accountId, $this->customerId, $this->initialBalance);
        $eur = new Currency('EUR');
        $depositAmount = new Money(5000, $eur);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot deposit money with a different currency.');
        $account->deposit($depositAmount);
    }

    public function testWithdrawMoneySuccessfully(): void
    {
        $account = new Account($this->accountId, $this->customerId, $this->initialBalance);
        $account->pullDomainEvents(); // Clear initial event

        $withdrawalAmount = new Money(3000, $this->usd); // 30.00 USD
        $account->withdraw($withdrawalAmount);

        $expectedBalance = new Money(7000, $this->usd); // 70.00 USD
        $this->assertTrue($expectedBalance->equals($account->getBalance()));

        $events = $account->pullDomainEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(MoneyWithdrawn::class, $events[0]);

        /** @var MoneyWithdrawn $event */
        $event = $events[0];
        $this->assertTrue($this->accountId->equals($event->getAccountId()));
        $this->assertTrue($withdrawalAmount->equals($event->getAmountWithdrawn()));
        $this->assertTrue($expectedBalance->equals($event->getNewBalance()));
    }

    public function testWithdrawMoneyWithDifferentCurrencyThrowsException(): void
    {
        $account = new Account($this->accountId, $this->customerId, $this->initialBalance);
        $eur = new Currency('EUR');
        $withdrawalAmount = new Money(3000, $eur);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot withdraw money with a different currency.');
        $account->withdraw($withdrawalAmount);
    }

    public function testWithdrawMoneyInsufficientFunds(): void
    {
        $account = new Account($this->accountId, $this->customerId, $this->initialBalance);
        $account->pullDomainEvents(); // Clear initial event

        $withdrawalAmount = new Money(12000, $this->usd); // 120.00 USD (more than 100.00)

        try {
            $account->withdraw($withdrawalAmount);
            $this->fail('DomainException for insufficient funds was not thrown.');
        } catch (DomainException $e) {
            $this->assertEquals('Insufficient funds.', $e->getMessage());
        }

        // Check that balance remains unchanged
        $this->assertTrue($this->initialBalance->equals($account->getBalance()));

        $events = $account->pullDomainEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(WithdrawalFailedDueToInsufficientFunds::class, $events[0]);

        /** @var WithdrawalFailedDueToInsufficientFunds $event */
        $event = $events[0];
        $this->assertTrue($this->accountId->equals($event->getAccountId()));
        $this->assertTrue($withdrawalAmount->equals($event->getAttemptedAmount()));
        $this->assertTrue($this->initialBalance->equals($event->getCurrentBalance()));
    }
}

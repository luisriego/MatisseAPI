<?php

declare(strict_types=1);

namespace Tests\Application\Command;

use App\Application\Command\CreateAccountCommand;
use App\Application\Command\CreateAccountCommandHandler;
use App\Application\Port\Out\AccountRepositoryInterface;
use App\Domain\Entity\Account;
use App\Domain\ValueObject\Currency;
use App\Domain\ValueObject\CustomerId;
use App\Domain\ValueObject\Money;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

final class CreateAccountCommandHandlerTest extends TestCase
{
    /** @var AccountRepositoryInterface&MockObject */
    private $accountRepositoryMock;
    private CreateAccountCommandHandler $handler;

    protected function setUp(): void
    {
        $this->accountRepositoryMock = $this->createMock(AccountRepositoryInterface::class);
        $this->handler = new CreateAccountCommandHandler($this->accountRepositoryMock);
    }

    public function testHandleCreatesAndSavesAccount(): void
    {
        $customerId = CustomerId::generate();
        $currency = new Currency('EUR');
        $initialBalance = new Money(5000, $currency); // 50.00 EUR

        $command = new CreateAccountCommand($customerId, $initialBalance);

        $this->accountRepositoryMock
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Account $account) use ($customerId, $initialBalance) {
                $this->assertTrue($customerId->equals($account->getCustomerId()));
                $this->assertTrue($initialBalance->equals($account->getBalance()));
                // AccountId is generated internally, so we can't easily check its exact value
                // but we can check it's not null or an empty string.
                $this->assertNotEmpty($account->getId()->toString());
                return true;
            }));

        $generatedAccountId = $this->handler->handle($command);

        $this->assertNotNull($generatedAccountId);
        // Potentially, if AccountId generation was predictable or injectable, we could assert its value
    }
}

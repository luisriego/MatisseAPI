<?php

declare(strict_types=1);

namespace Tests\Application\Command;

use App\Application\Command\ReceivePaymentFromUnitCommand;
use App\Application\Command\ReceivePaymentFromUnitCommandHandler;
use App\Application\Port\Out\UnitLedgerAccountRepositoryInterface;
use App\Application\Port\Out\UnitRepositoryInterface;
use App\Domain\Entity\Unit;
use App\Domain\Entity\UnitLedgerAccount;
use App\Domain\ValueObject\Currency;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\TransactionId;
use App\Domain\ValueObject\UnitId;
use DateTimeImmutable;
use DomainException;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

final class ReceivePaymentFromUnitCommandHandlerTest extends TestCase
{
    /** @var UnitLedgerAccountRepositoryInterface&MockObject */
    private $unitLedgerAccountRepositoryMock;
    /** @var UnitRepositoryInterface&MockObject */
    private $unitRepositoryMock;
    private ReceivePaymentFromUnitCommandHandler $handler;

    protected function setUp(): void
    {
        $this->unitLedgerAccountRepositoryMock = $this->createMock(UnitLedgerAccountRepositoryInterface::class);
        $this->unitRepositoryMock = $this->createMock(UnitRepositoryInterface::class);

        $this->handler = new ReceivePaymentFromUnitCommandHandler(
            $this->unitLedgerAccountRepositoryMock,
            $this->unitRepositoryMock
        );
    }

    public function testHandleSuccess(): void
    {
        $unitId = UnitId::generate();
        $command = new ReceivePaymentFromUnitCommand(
            $unitId,
            2500, // 25.00
            "CAD",
            "2024-01-25",
            "Credit Card"
        );

        $this->unitRepositoryMock->method('findById')
            ->with($this->callback(fn($arg) => $unitId->equals($arg)))
            ->willReturn($this->createMock(Unit::class));

        $mockLedgerAccount = $this->createMock(UnitLedgerAccount::class);
        $this->unitLedgerAccountRepositoryMock->method('findByUnitId')
            ->with($this->callback(fn($arg) => $unitId->equals($arg)))
            ->willReturn($mockLedgerAccount);

        $mockLedgerAccount->expects($this->once())->method('receivePayment')
            ->with(
                $this->callback(fn(Money $money) => $money->getAmount() === 2500 && $money->getCurrency()->getCode() === "CAD"),
                $this->isInstanceOf(TransactionId::class), // PaymentId is generated in handler
                $this->callback(fn(DateTimeImmutable $date) => $date->format('Y-m-d') === "2024-01-25"),
                $this->equalTo("Credit Card")
            );

        $this->unitLedgerAccountRepositoryMock->expects($this->once())->method('save')->with($mockLedgerAccount);

        $this->handler->handle($command);
    }

    public function testHandleUnitNotFoundThrowsException(): void
    {
        $unitId = UnitId::generate();
        $command = new ReceivePaymentFromUnitCommand($unitId, 100, "USD", "2024-01-01", "Cash");
        $this->unitRepositoryMock->method('findById')
            ->with($this->callback(fn($arg) => $unitId->equals($arg)))
            ->willReturn(null);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage("Unit with ID {$unitId->toString()} not found, cannot record payment.");
        $this->handler->handle($command);
    }

    public function testHandleUnitLedgerAccountNotFoundThrowsException(): void
    {
        $unitId = UnitId::generate();
        $command = new ReceivePaymentFromUnitCommand($unitId, 100, "USD", "2024-01-01", "Cash");

        $this->unitRepositoryMock->method('findById')
            ->with($this->callback(fn($arg) => $unitId->equals($arg)))
            ->willReturn($this->createMock(Unit::class));
        $this->unitLedgerAccountRepositoryMock->method('findByUnitId')
            ->with($this->callback(fn($arg) => $unitId->equals($arg)))
            ->willReturn(null);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage("UnitLedgerAccount for Unit ID {$unitId->toString()} not found.");
        $this->handler->handle($command);
    }

    public function testHandleInvalidDateFormatThrowsException(): void
    {
        $unitId = UnitId::generate();
        $command = new ReceivePaymentFromUnitCommand($unitId, 100, "USD", "invalid-date", "Cash");

        $this->unitRepositoryMock->method('findById')
            ->with($this->callback(fn($arg) => $unitId->equals($arg)))
            ->willReturn($this->createMock(Unit::class));
        $this->unitLedgerAccountRepositoryMock->method('findByUnitId')
            ->with($this->callback(fn($arg) => $unitId->equals($arg)))
            ->willReturn($this->createMock(UnitLedgerAccount::class));

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid payment date format: invalid-date. Please use YYYY-MM-DD.");
        $this->handler->handle($command);
    }
}

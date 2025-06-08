<?php

declare(strict_types=1);

namespace Tests\Application\Command;

use App\Application\Command\IssueFeeToUnitCommand;
use App\Application\Command\IssueFeeToUnitCommandHandler;
use App\Application\Port\Out\FeeItemRepositoryInterface;
use App\Application\Port\Out\UnitLedgerAccountRepositoryInterface;
use App\Application\Port\Out\UnitRepositoryInterface;
use App\Domain\Entity\FeeItem;
use App\Domain\Entity\Unit;
use App\Domain\Entity\UnitLedgerAccount;
use App\Domain\ValueObject\CondominiumId; // For setting up mocks
use App\Domain\ValueObject\Currency;
use App\Domain\ValueObject\FeeItemId;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\UnitId;
use DateTimeImmutable;
use DomainException;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

final class IssueFeeToUnitCommandHandlerTest extends TestCase
{
    /** @var UnitLedgerAccountRepositoryInterface&MockObject */
    private $unitLedgerAccountRepositoryMock;
    /** @var UnitRepositoryInterface&MockObject */
    private $unitRepositoryMock;
    /** @var FeeItemRepositoryInterface&MockObject */
    private $feeItemRepositoryMock;
    private IssueFeeToUnitCommandHandler $handler;

    protected function setUp(): void
    {
        $this->unitLedgerAccountRepositoryMock = $this->createMock(UnitLedgerAccountRepositoryInterface::class);
        $this->unitRepositoryMock = $this->createMock(UnitRepositoryInterface::class);
        $this->feeItemRepositoryMock = $this->createMock(FeeItemRepositoryInterface::class);

        $this->handler = new IssueFeeToUnitCommandHandler(
            $this->unitLedgerAccountRepositoryMock,
            $this->unitRepositoryMock,
            $this->feeItemRepositoryMock
        );
    }

    public function testHandleSuccessWhenLedgerExists(): void
    {
        $unitId = UnitId::generate();
        $feeItemId = FeeItemId::generate();
        $condoId = CondominiumId::generate(); // Assume both unit and fee item belong to this

        $command = new IssueFeeToUnitCommand($unitId, $feeItemId, 7500, "USD", "2024-02-01", "Monthly Dues");

        $mockUnit = $this->createMock(Unit::class);
        $mockUnit->method('getCondominiumId')->willReturn($condoId);
        $this->unitRepositoryMock->method('findById')
            ->with($this->callback(fn($arg) => $unitId->equals($arg)))
            ->willReturn($mockUnit);

        $mockFeeItem = $this->createMock(FeeItem::class);
        $mockFeeItem->method('getCondominiumId')->willReturn($condoId);
        $this->feeItemRepositoryMock->method('findById')
            ->with($this->callback(fn($arg) => $feeItemId->equals($arg)))
            ->willReturn($mockFeeItem);

        $mockLedgerAccount = $this->createMock(UnitLedgerAccount::class);
        $this->unitLedgerAccountRepositoryMock->method('findByUnitId')
            ->with($this->callback(fn($arg) => $unitId->equals($arg)))
            ->willReturn($mockLedgerAccount);

        $mockLedgerAccount->expects($this->once())->method('applyFee')
            ->with(
                $this->equalTo($feeItemId),
                $this->callback(fn(Money $money) => $money->getAmount() === 7500 && $money->getCurrency()->getCode() === "USD"),
                $this->callback(fn(DateTimeImmutable $date) => $date->format('Y-m-d') === "2024-02-01"),
                $this->equalTo("Monthly Dues")
            );

        $this->unitLedgerAccountRepositoryMock->expects($this->once())->method('save')->with($mockLedgerAccount);

        $this->handler->handle($command);
    }

    public function testHandleSuccessCreatesLedgerIfNotExists(): void
    {
        $unitId = UnitId::generate();
        $feeItemId = FeeItemId::generate();
        $condoId = CondominiumId::generate();

        $command = new IssueFeeToUnitCommand($unitId, $feeItemId, 7500, "EUR", "2024-02-01", "Service Charge");

        $mockUnit = $this->createMock(Unit::class);
        $mockUnit->method('getCondominiumId')->willReturn($condoId);
        $this->unitRepositoryMock->method('findById')
            ->with($this->callback(fn($arg) => $unitId->equals($arg)))
            ->willReturn($mockUnit);

        $mockFeeItem = $this->createMock(FeeItem::class);
        $mockFeeItem->method('getCondominiumId')->willReturn($condoId);
        $this->feeItemRepositoryMock->method('findById')
            ->with($this->callback(fn($arg) => $feeItemId->equals($arg)))
            ->willReturn($mockFeeItem);

        $this->unitLedgerAccountRepositoryMock->method('findByUnitId')
            ->with($this->callback(fn($arg) => $unitId->equals($arg)))
            ->willReturn(null); // Ledger does not exist

        // Expect save to be called twice: once for new ledger, once after applying fee
        $this->unitLedgerAccountRepositoryMock->expects($this->exactly(2))->method('save')
            ->with($this->isInstanceOf(UnitLedgerAccount::class));
            // More specific assertions on the saved account could be added if UnitLedgerAccount::createNew was not static
            // or if we could inspect the state of the mock.

        $this->handler->handle($command);
    }

    public function testHandleUnitNotFoundThrowsException(): void
    {
        $unitId = UnitId::generate();
        $command = new IssueFeeToUnitCommand($unitId, FeeItemId::generate(), 100, "USD", "2024-01-01");
        $this->unitRepositoryMock->method('findById')
            ->with($this->callback(fn($arg) => $unitId->equals($arg)))
            ->willReturn(null);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage("Unit with ID {$unitId->toString()} not found.");
        $this->handler->handle($command);
    }

    public function testHandleFeeItemNotFoundThrowsException(): void
    {
        $unitId = UnitId::generate();
        $feeItemId = FeeItemId::generate();
        $command = new IssueFeeToUnitCommand($unitId, $feeItemId, 100, "USD", "2024-01-01");

        $this->unitRepositoryMock->method('findById')
            ->with($this->callback(fn($arg) => $unitId->equals($arg)))
            ->willReturn($this->createMock(Unit::class));
        $this->feeItemRepositoryMock->method('findById')
            ->with($this->callback(fn($arg) => $feeItemId->equals($arg)))
            ->willReturn(null);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage("FeeItem with ID {$feeItemId->toString()} not found.");
        $this->handler->handle($command);
    }

    public function testHandleFeeItemDoesNotBelongToCondoThrowsException(): void
    {
        $unitId = UnitId::generate();
        $feeItemId = FeeItemId::generate();
        $unitCondoId = CondominiumId::generate();
        $feeItemCondoId = CondominiumId::generate(); // Different Condo ID

        $command = new IssueFeeToUnitCommand($unitId, $feeItemId, 100, "USD", "2024-01-01");

        $mockUnit = $this->createMock(Unit::class);
        $mockUnit->method('getCondominiumId')->willReturn($unitCondoId);
        $this->unitRepositoryMock->method('findById')
            ->with($this->callback(fn($arg) => $unitId->equals($arg)))
            ->willReturn($mockUnit);

        $mockFeeItem = $this->createMock(FeeItem::class);
        $mockFeeItem->method('getCondominiumId')->willReturn($feeItemCondoId);
        $this->feeItemRepositoryMock->method('findById')
            ->with($this->callback(fn($arg) => $feeItemId->equals($arg)))
            ->willReturn($mockFeeItem);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage("FeeItem does not belong to the unit's condominium.");
        $this->handler->handle($command);
    }
}

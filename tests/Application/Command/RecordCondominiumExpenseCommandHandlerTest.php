<?php

declare(strict_types=1);

namespace Tests\Application\Command;

use App\Application\Command\RecordCondominiumExpenseCommand;
use App\Application\Command\RecordCondominiumExpenseCommandHandler;
use App\Application\Port\Out\CondominiumRepositoryInterface;
use App\Application\Port\Out\ExpenseCategoryRepositoryInterface;
use App\Application\Port\Out\ExpenseRepositoryInterface;
use App\Domain\Entity\Condominium;
use App\Domain\Entity\Expense;
use App\Domain\Entity\ExpenseCategory;
use App\Domain\ValueObject\CondominiumId;
use App\Domain\ValueObject\ExpenseCategoryId;
use App\Domain\ValueObject\Address; // For Condo creation
use App\Domain\ValueObject\Currency; // For Money
use App\Domain\ValueObject\Money;    // For Money
use DateTimeImmutable;
use DomainException;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

final class RecordCondominiumExpenseCommandHandlerTest extends TestCase
{
    /** @var ExpenseRepositoryInterface&MockObject */
    private $expenseRepositoryMock;
    /** @var CondominiumRepositoryInterface&MockObject */
    private $condominiumRepositoryMock;
    /** @var ExpenseCategoryRepositoryInterface&MockObject */
    private $expenseCategoryRepositoryMock;
    private RecordCondominiumExpenseCommandHandler $handler;

    protected function setUp(): void
    {
        $this->expenseRepositoryMock = $this->createMock(ExpenseRepositoryInterface::class);
        $this->condominiumRepositoryMock = $this->createMock(CondominiumRepositoryInterface::class);
        $this->expenseCategoryRepositoryMock = $this->createMock(ExpenseCategoryRepositoryInterface::class);

        $this->handler = new RecordCondominiumExpenseCommandHandler(
            $this->expenseRepositoryMock,
            $this->condominiumRepositoryMock,
            $this->expenseCategoryRepositoryMock
        );
    }

    public function testHandleSuccess(): void
    {
        $condoId = CondominiumId::generate();
        $categoryId = ExpenseCategoryId::generate();
        $command = new RecordCondominiumExpenseCommand(
            $condoId,
            $categoryId,
            "Gardening services",
            15000, // 150.00
            "USD",
            "2024-01-10"
        );

        $mockCondominium = $this->createMock(Condominium::class);
        $this->condominiumRepositoryMock->method('findById')->with($condoId)->willReturn($mockCondominium);

        $mockExpenseCategory = $this->createMock(ExpenseCategory::class);
        $mockExpenseCategory->method('getCondominiumId')->willReturn($condoId); // Ensure category belongs to condo
        $this->expenseCategoryRepositoryMock->method('findById')->with($categoryId)->willReturn($mockExpenseCategory);

        $this->expenseRepositoryMock
            ->expects($this->once())
            ->method('save')
            ->with($this->isInstanceOf(Expense::class));

        $expenseId = $this->handler->handle($command);
        $this->assertNotNull($expenseId);
    }

    public function testHandleCondominiumNotFoundThrowsException(): void
    {
        $condoId = CondominiumId::generate();
        $categoryId = ExpenseCategoryId::generate();
        $command = new RecordCondominiumExpenseCommand($condoId, $categoryId, "Test", 100, "USD", "2024-01-01");

        $this->condominiumRepositoryMock->method('findById')->with($condoId)->willReturn(null);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage("Condominium with ID {$condoId->toString()} not found.");
        $this->handler->handle($command);
    }

    public function testHandleExpenseCategoryNotFoundThrowsException(): void
    {
        $condoId = CondominiumId::generate();
        $categoryId = ExpenseCategoryId::generate();
        $command = new RecordCondominiumExpenseCommand($condoId, $categoryId, "Test", 100, "USD", "2024-01-01");

        $this->condominiumRepositoryMock->method('findById')->with($condoId)->willReturn($this->createMock(Condominium::class));
        $this->expenseCategoryRepositoryMock->method('findById')->with($categoryId)->willReturn(null);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage("ExpenseCategory with ID {$categoryId->toString()} not found.");
        $this->handler->handle($command);
    }

    public function testHandleExpenseCategoryDoesNotBelongToCondoThrowsException(): void
    {
        $condoId = CondominiumId::generate();
        $otherCondoId = CondominiumId::generate(); // Different Condo ID
        $categoryId = ExpenseCategoryId::generate();

        $command = new RecordCondominiumExpenseCommand($condoId, $categoryId, "Test", 100, "USD", "2024-01-01");

        $this->condominiumRepositoryMock->method('findById')->with($condoId)->willReturn($this->createMock(Condominium::class));

        $mockExpenseCategory = $this->createMock(ExpenseCategory::class);
        $mockExpenseCategory->method('getCondominiumId')->willReturn($otherCondoId); // Category belongs to another condo
        $this->expenseCategoryRepositoryMock->method('findById')->with($categoryId)->willReturn($mockExpenseCategory);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage("ExpenseCategory does not belong to the specified condominium.");
        $this->handler->handle($command);
    }

    public function testHandleInvalidDateFormatThrowsException(): void
    {
        $command = new RecordCondominiumExpenseCommand(
            CondominiumId::generate(),
            ExpenseCategoryId::generate(),
            "Test", 100, "USD", "invalid-date"
        );
        // Mock other dependencies to avoid errors before date parsing
        $expectedCondoId = $command->condominiumId;
        $expectedCategoryId = $command->expenseCategoryId;

        $mockCondominium = $this->createMock(Condominium::class);
        $this->condominiumRepositoryMock->method('findById')
            ->with($this->callback(function($arg) use ($expectedCondoId) { return $expectedCondoId->equals($arg); }))
            ->willReturn($mockCondominium);

        $mockExpenseCategory = $this->createMock(ExpenseCategory::class);
        // Ensure the mock ExpenseCategory returns the CondominiumId that matches the command's CondoId
        // This is critical for the `if (!$expenseCategory->getCondominiumId()->equals($command->condominiumId))` check in the handler
        $mockExpenseCategory->method('getCondominiumId')->willReturn($expectedCondoId);

        $this->expenseCategoryRepositoryMock->method('findById')
            ->with($this->callback(function($arg) use ($expectedCategoryId) { return $expectedCategoryId->equals($arg); }))
            ->willReturn($mockExpenseCategory);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid expense date format: invalid-date. Please use YYYY-MM-DD.");
        $this->handler->handle($command);
    }
}

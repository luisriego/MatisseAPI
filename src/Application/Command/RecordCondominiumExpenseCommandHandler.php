<?php

declare(strict_types=1);

namespace App\Application\Command;

use App\Application\Port\Out\CondominiumRepositoryInterface;
use App\Application\Port\Out\ExpenseCategoryRepositoryInterface;
use App\Application\Port\Out\ExpenseRepositoryInterface;
use App\Domain\Entity\Expense;
use App\Domain\ValueObject\Currency;
use App\Domain\ValueObject\ExpenseId;
use App\Domain\ValueObject\Money;
use DateTimeImmutable;
use DomainException; // For validation errors like not found

final class RecordCondominiumExpenseCommandHandler
{
    private ExpenseRepositoryInterface $expenseRepository;
    private CondominiumRepositoryInterface $condominiumRepository;
    private ExpenseCategoryRepositoryInterface $expenseCategoryRepository;

    public function __construct(
        ExpenseRepositoryInterface $expenseRepository,
        CondominiumRepositoryInterface $condominiumRepository,
        ExpenseCategoryRepositoryInterface $expenseCategoryRepository
    ) {
        $this->expenseRepository = $expenseRepository;
        $this->condominiumRepository = $condominiumRepository;
        $this->expenseCategoryRepository = $expenseCategoryRepository;
    }

    public function handle(RecordCondominiumExpenseCommand $command): ExpenseId
    {
        // Validate existence of Condominium
        $condominium = $this->condominiumRepository->findById($command->condominiumId);
        if ($condominium === null) {
            throw new DomainException("Condominium with ID {$command->condominiumId->toString()} not found.");
        }

        // Validate existence of ExpenseCategory
        $expenseCategory = $this->expenseCategoryRepository->findById($command->expenseCategoryId);
        if ($expenseCategory === null) {
            throw new DomainException("ExpenseCategory with ID {$command->expenseCategoryId->toString()} not found.");
        }
        // Ensure category belongs to the condominium if that's a rule
        if (!$expenseCategory->getCondominiumId()->equals($command->condominiumId)) {
             throw new DomainException("ExpenseCategory does not belong to the specified condominium.");
        }

        $expenseId = ExpenseId::generate();
        $amount = new Money($command->amountCents, new Currency($command->currencyCode));

        try {
            $expenseDate = new DateTimeImmutable($command->expenseDate);
        } catch (\Exception $e) {
            throw new \InvalidArgumentException("Invalid expense date format: {$command->expenseDate}. Please use YYYY-MM-DD.");
        }

        $expense = Expense::createNew(
            $expenseId,
            $command->condominiumId,
            $command->expenseCategoryId,
            $command->description,
            $amount,
            $expenseDate
        );

        $this->expenseRepository->save($expense);

        return $expenseId;
    }
}

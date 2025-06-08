<?php

declare(strict_types=1);

namespace App\Application\Port\Out;

use App\Domain\Entity\Expense;
use App\Domain\ValueObject\ExpenseId;

interface ExpenseRepositoryInterface
{
    public function save(Expense $expense): void;
    public function findById(ExpenseId $expenseId): ?Expense;
}

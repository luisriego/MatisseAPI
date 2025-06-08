<?php

declare(strict_types=1);

namespace App\Application\Port\Out;

use App\Domain\Entity\ExpenseCategory;
use App\Domain\ValueObject\ExpenseCategoryId;

interface ExpenseCategoryRepositoryInterface
{
    public function save(ExpenseCategory $expenseCategory): void; // Added save for consistency
    public function findById(ExpenseCategoryId $expenseCategoryId): ?ExpenseCategory;
}

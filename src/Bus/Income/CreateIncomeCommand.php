<?php

declare(strict_types=1);

namespace App\Bus\Income;

readonly class CreateIncomeCommand
{
    public function __construct(
        public int $incomeTypeId,
        public int    $amount,
        public ?string $description,
        public string $dueDate,
        public string $residentId,
    ) {
    }
}

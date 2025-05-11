<?php

declare(strict_types=1);

namespace App\Bus\Slip;

readonly class GenerateSlipsCommand
{
    public function __construct(
        public string $targetMonthDateString)
    {

    }
}

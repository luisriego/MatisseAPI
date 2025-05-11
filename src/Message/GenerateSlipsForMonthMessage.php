<?php

namespace App\Message;

class GenerateSlipsForMonthMessage
{
    public function __construct(
        public readonly string $targetMonthDateString
    ) {
    }
}

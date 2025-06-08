<?php

declare(strict_types=1);

namespace App\Application\Query;

// Using string for ID for simplicity in Query message,
// could use CondominiumId value object if preferred and handler converts.
final class GetCondominiumDetailsQuery
{
    public string $condominiumId;

    public function __construct(string $condominiumId)
    {
        $this->condominiumId = $condominiumId;
    }
}

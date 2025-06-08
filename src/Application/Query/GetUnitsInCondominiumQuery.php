<?php

declare(strict_types=1);

namespace App\Application\Query;

final class GetUnitsInCondominiumQuery
{
    public string $condominiumId;

    public function __construct(string $condominiumId)
    {
        $this->condominiumId = $condominiumId;
    }
}

<?php

declare(strict_types=1);

namespace App\Application\Port\Out;

use App\Domain\Entity\Condominium;
use App\Domain\ValueObject\CondominiumId;

interface CondominiumRepositoryInterface
{
    public function save(Condominium $condominium): void;
    public function findById(CondominiumId $condominiumId): ?Condominium;
}

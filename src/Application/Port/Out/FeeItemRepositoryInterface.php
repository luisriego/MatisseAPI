<?php

declare(strict_types=1);

namespace App\Application\Port\Out;

use App\Domain\Entity\FeeItem;
use App\Domain\ValueObject\FeeItemId;

interface FeeItemRepositoryInterface
{
    public function save(FeeItem $feeItem): void; // Added save if FeeItem becomes event-sourced for changes
    public function findById(FeeItemId $feeItemId): ?FeeItem;
}

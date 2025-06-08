<?php

declare(strict_types=1);

namespace App\Application\Port\Out;

use App\Domain\Entity\Owner;
use App\Domain\ValueObject\OwnerId;

interface OwnerRepositoryInterface
{
    public function save(Owner $owner): void;
    public function findById(OwnerId $ownerId): ?Owner;
}

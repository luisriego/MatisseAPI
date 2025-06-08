<?php

declare(strict_types=1);

namespace App\Application\DTO;

final class UnitSummaryDTO
{
    public string $id;
    public string $identifier;
    public ?string $ownerId; // OwnerId can be null
    // public string $condominiumId; // Optional, if context isn't providing it
    // public string $createdAt; // Optional
    // public string $updatedAt; // Optional

    public function __construct(
        string $id,
        string $identifier,
        ?string $ownerId
        // string $condominiumId = '', // Optional
        // string $createdAt = '',     // Optional
        // string $updatedAt = ''      // Optional
    ) {
        $this->id = $id;
        $this->identifier = $identifier;
        $this->ownerId = $ownerId;
        // $this->condominiumId = $condominiumId;
        // $this->createdAt = $createdAt;
        // $this->updatedAt = $updatedAt;
    }
}

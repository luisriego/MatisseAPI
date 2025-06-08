<?php

declare(strict_types=1);

namespace App\Application\DTO;

final class CondominiumDetailsDTO
{
    public string $id;
    public string $name;
    public string $addressStreet;
    public string $addressCity;
    public string $addressPostalCode;
    public string $addressCountry;
    // public string $createdAt; // Optional, if needed from read model
    // public string $updatedAt; // Optional, if needed from read model

    public function __construct(
        string $id,
        string $name,
        string $addressStreet,
        string $addressCity,
        string $addressPostalCode,
        string $addressCountry
        // string $createdAt = '', // Optional
        // string $updatedAt = ''  // Optional
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->addressStreet = $addressStreet;
        $this->addressCity = $addressCity;
        $this->addressPostalCode = $addressPostalCode;
        $this->addressCountry = $addressCountry;
        // $this->createdAt = $createdAt;
        // $this->updatedAt = $updatedAt;
    }
}

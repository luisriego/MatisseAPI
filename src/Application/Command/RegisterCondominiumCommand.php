<?php

declare(strict_types=1);

namespace App\Application\Command;

// Assuming Address fields are passed directly for simplicity.
// A real app might use an AddressDTO or individual Value Objects.
final class RegisterCondominiumCommand
{
    public string $name;
    public string $addressStreet;
    public string $addressCity;
    public string $addressPostalCode;
    public string $addressCountry;

    public function __construct(
        string $name,
        string $addressStreet,
        string $addressCity,
        string $addressPostalCode,
        string $addressCountry
    ) {
        $this->name = $name;
        $this->addressStreet = $addressStreet;
        $this->addressCity = $addressCity;
        $this->addressPostalCode = $addressPostalCode;
        $this->addressCountry = $addressCountry;
    }
}

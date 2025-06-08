<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

final class Address
{
    private string $street;
    private string $city;
    private string $postalCode;
    private string $country;

    public function __construct(string $street, string $city, string $postalCode, string $country)
    {
        // Basic validation can be added here if needed (e.g., not empty)
        $this->street = $street;
        $this->city = $city;
        $this->postalCode = $postalCode;
        $this->country = $country;
    }

    public function getStreet(): string
    {
        return $this->street;
    }

    public function getCity(): string
    {
        return $this->city;
    }

    public function getPostalCode(): string
    {
        return $this->postalCode;
    }

    public function getCountry(): string
    {
        return $this->country;
    }

    public function equals(Address $other): bool
    {
        return $this->street === $other->street &&
               $this->city === $other->city &&
               $this->postalCode === $other->postalCode &&
               $this->country === $other->country;
    }

    public function __toString(): string
    {
        return sprintf('%s, %s, %s, %s', $this->street, $this->city, $this->postalCode, $this->country);
    }
}

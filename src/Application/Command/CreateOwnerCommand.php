<?php

declare(strict_types=1);

namespace App\Application\Command;

// No specific Value Objects needed here if using strings,
// but real app might use EmailValueObject, PhoneNumberValueObject
final class CreateOwnerCommand
{
    public string $name;
    public string $email;
    public string $phoneNumber;

    public function __construct(string $name, string $email, string $phoneNumber)
    {
        $this->name = $name;
        $this->email = $email;
        $this->phoneNumber = $phoneNumber;
    }
}

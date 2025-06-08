<?php

declare(strict_types=1);

namespace Tests\Fixture\Mother;

use App\Domain\Entity\Owner;
use App\Domain\ValueObject\OwnerId;

class OwnerMother
{
    public static function typical(): Owner
    {
        return Owner::createNew(
            OwnerId::generate(),
            'Typical Owner Name', // More distinct name
            'typical.owner@example.com',
            '555-0101'
        );
    }

    public static function withIdAndEmail(OwnerId $id, string $email): Owner
    {
        return Owner::createNew(
            $id,
            'Specific Owner by ID',
            $email,
            '555-0202'
        );
    }

    public static function withSpecifics(
        ?OwnerId $id = null,
        string $name = 'Specific Owner',
        string $email = 'specific.owner@example.net',
        string $phoneNumber = '555-0303'
    ): Owner {
        return Owner::createNew(
            $id ?? OwnerId::generate(),
            $name,
            $email,
            $phoneNumber
        );
    }
}

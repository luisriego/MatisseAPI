<?php

declare(strict_types=1);

namespace Tests\Fixture\Mother;

use App\Domain\Entity\Condominium;
use App\Domain\ValueObject\Address;
use App\Domain\ValueObject\CondominiumId;

class CondominiumMother
{
    public static function typical(): Condominium
    {
        return Condominium::createNew(
            CondominiumId::generate(),
            'Typical Condominium Plaza', // More distinct name
            new Address('100 Main Street', 'Metropolis', '54321', 'USA')
        );
    }

    public static function withIdAndName(CondominiumId $id, string $name): Condominium
    {
        return Condominium::createNew(
            $id,
            $name,
            new Address('200 Oak Avenue', 'Gotham', '67890', 'USA')
        );
    }

    public static function withSpecifics(
        ?CondominiumId $id = null,
        string $name = 'Specific Condo',
        ?Address $address = null
    ): Condominium {
        return Condominium::createNew(
            $id ?? CondominiumId::generate(),
            $name,
            $address ?? new Address('777 Pine Ln', 'Star City', '11223', 'USA')
        );
    }
}

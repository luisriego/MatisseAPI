<?php

declare(strict_types=1);

namespace App\Application\Command;

use App\Application\Port\Out\CondominiumRepositoryInterface;
use App\Domain\Entity\Condominium;
use App\Domain\ValueObject\Address;
use App\Domain\ValueObject\CondominiumId;

final class RegisterCondominiumCommandHandler
{
    private CondominiumRepositoryInterface $condominiumRepository;

    public function __construct(CondominiumRepositoryInterface $condominiumRepository)
    {
        $this->condominiumRepository = $condominiumRepository;
    }

    public function handle(RegisterCondominiumCommand $command): CondominiumId
    {
        $condominiumId = CondominiumId::generate();
        $address = new Address(
            $command->addressStreet,
            $command->addressCity,
            $command->addressPostalCode,
            $command->addressCountry
        );

        // The Condominium entity's createNew static method handles recording the event
        $condominium = Condominium::createNew(
            $condominiumId,
            $command->name,
            $address
        );

        $this->condominiumRepository->save($condominium);

        return $condominiumId;
    }
}

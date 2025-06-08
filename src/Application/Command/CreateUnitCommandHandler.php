<?php

declare(strict_types=1);

namespace App\Application\Command;

use App\Application\Port\Out\UnitRepositoryInterface;
// Optional: use App\Application\Port\Out\CondominiumRepositoryInterface;
use App\Domain\Entity\Unit;
use App\Domain\ValueObject\UnitId;
// Potentially throw an exception if Condominium does not exist
// use App\Application\Exception\CondominiumNotFoundException;

final class CreateUnitCommandHandler
{
    private UnitRepositoryInterface $unitRepository;
    // private CondominiumRepositoryInterface $condominiumRepository; // Optional

    public function __construct(
        UnitRepositoryInterface $unitRepository
        // CondominiumRepositoryInterface $condominiumRepository // Optional
    ) {
        $this->unitRepository = $unitRepository;
        // $this->condominiumRepository = $condominiumRepository;
    }

    public function handle(CreateUnitCommand $command): UnitId
    {
        // Optional: Check if condominium exists
        // $condominium = $this->condominiumRepository->findById($command->condominiumId);
        // if ($condominium === null) {
        //     throw new CondominiumNotFoundException("Condominium with id {$command->condominiumId->toString()} not found.");
        // }

        $unitId = UnitId::generate();

        $unit = Unit::createNew(
            $unitId,
            $command->condominiumId,
            $command->unitIdentifier
        );

        $this->unitRepository->save($unit);

        return $unitId;
    }
}

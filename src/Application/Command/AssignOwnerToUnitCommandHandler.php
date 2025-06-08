<?php

declare(strict_types=1);

namespace App\Application\Command;

use App\Application\Port\Out\UnitRepositoryInterface;
use App\Application\Port\Out\OwnerRepositoryInterface;
// Define custom exceptions for "not found" cases for clarity
use DomainException; // Or more specific App exceptions like UnitNotFound, OwnerNotFound

final class AssignOwnerToUnitCommandHandler
{
    private UnitRepositoryInterface $unitRepository;
    private OwnerRepositoryInterface $ownerRepository;

    public function __construct(
        UnitRepositoryInterface $unitRepository,
        OwnerRepositoryInterface $ownerRepository
    ) {
        $this->unitRepository = $unitRepository;
        $this->ownerRepository = $ownerRepository;
    }

    public function handle(AssignOwnerToUnitCommand $command): void
    {
        $unit = $this->unitRepository->findById($command->unitId);
        if ($unit === null) {
            // throw new UnitNotFoundException("Unit with id {$command->unitId->toString()} not found.");
            throw new DomainException("Unit with id {$command->unitId->toString()} not found.");
        }

        $owner = $this->ownerRepository->findById($command->ownerId);
        if ($owner === null) {
            // throw new OwnerNotFoundException("Owner with id {$command->ownerId->toString()} not found.");
            throw new DomainException("Owner with id {$command->ownerId->toString()} not found.");
        }

        $unit->assignOwner($command->ownerId); // This method in Unit entity records the event

        $this->unitRepository->save($unit);
    }
}

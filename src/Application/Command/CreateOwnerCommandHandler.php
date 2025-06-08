<?php

declare(strict_types=1);

namespace App\Application\Command;

use App\Application\Port\Out\OwnerRepositoryInterface;
use App\Domain\Entity\Owner;
use App\Domain\ValueObject\OwnerId;

final class CreateOwnerCommandHandler
{
    private OwnerRepositoryInterface $ownerRepository;

    public function __construct(OwnerRepositoryInterface $ownerRepository)
    {
        $this->ownerRepository = $ownerRepository;
    }

    public function handle(CreateOwnerCommand $command): OwnerId
    {
        $ownerId = OwnerId::generate();

        // The Owner entity's createNew static method handles recording the OwnerCreatedEvent
        $owner = Owner::createNew(
            $ownerId,
            $command->name,
            $command->email,
            $command->phoneNumber
        );

        $this->ownerRepository->save($owner);

        return $ownerId;
    }
}

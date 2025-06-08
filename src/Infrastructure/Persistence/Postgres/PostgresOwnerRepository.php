<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Postgres;

use App\Application\Port\Out\OwnerRepositoryInterface;
use App\Application\Port\Out\EventStoreInterface;
use App\Domain\Entity\Owner;
use App\Domain\ValueObject\OwnerId;
use App\Infrastructure\Projection\ProjectionManager;

final class PostgresOwnerRepository implements OwnerRepositoryInterface
{
    private EventStoreInterface $eventStore;
    private ProjectionManager $projectionManager;

    public function __construct(EventStoreInterface $eventStore, ProjectionManager $projectionManager)
    {
        $this->eventStore = $eventStore;
        $this->projectionManager = $projectionManager;
    }

    public function save(Owner $owner): void
    {
        $domainEvents = $owner->pullDomainEvents();
        if (!empty($domainEvents)) {
            $this->eventStore->append(...$domainEvents);
            $this->projectionManager->dispatch(...$domainEvents);
        }
    }

    public function findById(OwnerId $ownerId): ?Owner
    {
        $events = $this->eventStore->getEventsForAggregate($ownerId->toString());

        if (empty($events)) {
            return null;
        }

        return Owner::reconstituteFromHistory(...$events);
    }
}

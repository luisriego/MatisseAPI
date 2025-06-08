<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Postgres;

use App\Application\Port\Out\UnitRepositoryInterface;
use App\Application\Port\Out\EventStoreInterface;
use App\Domain\Entity\Unit;
use App\Domain\ValueObject\UnitId;
use App\Infrastructure\Projection\ProjectionManager;

final class PostgresUnitRepository implements UnitRepositoryInterface
{
    private EventStoreInterface $eventStore;
    private ProjectionManager $projectionManager;

    public function __construct(EventStoreInterface $eventStore, ProjectionManager $projectionManager)
    {
        $this->eventStore = $eventStore;
        $this->projectionManager = $projectionManager;
    }

    public function save(Unit $unit): void
    {
        $domainEvents = $unit->pullDomainEvents();
        if (!empty($domainEvents)) {
            $this->eventStore->append(...$domainEvents);
            $this->projectionManager->dispatch(...$domainEvents);
        }
    }

    public function findById(UnitId $unitId): ?Unit
    {
        $events = $this->eventStore->getEventsForAggregate($unitId->toString());

        if (empty($events)) {
            return null;
        }

        return Unit::reconstituteFromHistory(...$events);
    }
}

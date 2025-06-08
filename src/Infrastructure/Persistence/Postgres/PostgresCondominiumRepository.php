<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Postgres;

use App\Application\Port\Out\CondominiumRepositoryInterface;
use App\Application\Port\Out\EventStoreInterface;
use App\Domain\Entity\Condominium;
use App\Domain\ValueObject\CondominiumId;
use App\Infrastructure\Projection\ProjectionManager;

final class PostgresCondominiumRepository implements CondominiumRepositoryInterface
{
    private EventStoreInterface $eventStore;
    private ProjectionManager $projectionManager;

    public function __construct(EventStoreInterface $eventStore, ProjectionManager $projectionManager)
    {
        $this->eventStore = $eventStore;
        $this->projectionManager = $projectionManager;
    }

    public function save(Condominium $condominium): void
    {
        $domainEvents = $condominium->pullDomainEvents();
        if (!empty($domainEvents)) {
            $this->eventStore->append(...$domainEvents);
            $this->projectionManager->dispatch(...$domainEvents);
        }
    }

    public function findById(CondominiumId $condominiumId): ?Condominium
    {
        $events = $this->eventStore->getEventsForAggregate($condominiumId->toString());

        if (empty($events)) {
            return null;
        }

        return Condominium::reconstituteFromHistory(...$events);
    }
}

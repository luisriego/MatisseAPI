<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Postgres;

use App\Application\Port\Out\UnitLedgerAccountRepositoryInterface;
use App\Application\Port\Out\EventStoreInterface;
use App\Domain\Entity\UnitLedgerAccount;
use App\Domain\ValueObject\UnitId;
use App\Infrastructure\Projection\ProjectionManager;

final class PostgresUnitLedgerAccountRepository implements UnitLedgerAccountRepositoryInterface
{
    private EventStoreInterface $eventStore;
    private ProjectionManager $projectionManager;

    public function __construct(EventStoreInterface $eventStore, ProjectionManager $projectionManager)
    {
        $this->eventStore = $eventStore;
        $this->projectionManager = $projectionManager;
    }

    public function save(UnitLedgerAccount $account): void
    {
        $domainEvents = $account->pullDomainEvents();
        if (!empty($domainEvents)) {
            $this->eventStore->append(...$domainEvents);
            $this->projectionManager->dispatch(...$domainEvents);
        }
    }

    public function findByUnitId(UnitId $unitId): ?UnitLedgerAccount
    {
        // UnitLedgerAccount's aggregate ID is the UnitId
        $events = $this->eventStore->getEventsForAggregate($unitId->toString());

        if (empty($events)) {
            return null;
        }

        return UnitLedgerAccount::reconstituteFromHistory(...$events);
    }
}

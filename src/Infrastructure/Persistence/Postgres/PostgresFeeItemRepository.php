<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Postgres;

use App\Application\Port\Out\FeeItemRepositoryInterface;
use App\Application\Port\Out\EventStoreInterface;
use App\Domain\Entity\FeeItem;
use App\Domain\ValueObject\FeeItemId;
use App\Infrastructure\Projection\ProjectionManager;

final class PostgresFeeItemRepository implements FeeItemRepositoryInterface
{
    private EventStoreInterface $eventStore;
    private ProjectionManager $projectionManager; // Assuming FeeItem changes might also be projected

    public function __construct(EventStoreInterface $eventStore, ProjectionManager $projectionManager)
    {
        $this->eventStore = $eventStore;
        $this->projectionManager = $projectionManager;
    }

    public function save(FeeItem $feeItem): void
    {
        $domainEvents = $feeItem->pullDomainEvents();
        if (!empty($domainEvents)) {
            $this->eventStore->append(...$domainEvents);
            // Dispatch if FeeItem events are projected (e.g., to a FeeItem read model table)
            $this->projectionManager->dispatch(...$domainEvents);
        }
    }

    public function findById(FeeItemId $feeItemId): ?FeeItem
    {
        $events = $this->eventStore->getEventsForAggregate($feeItemId->toString());

        if (empty($events)) {
            return null;
        }

        return FeeItem::reconstituteFromHistory(...$events);
    }
}

<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Postgres;

use App\Application\Port\Out\ExpenseCategoryRepositoryInterface;
use App\Application\Port\Out\EventStoreInterface;
use App\Domain\Entity\ExpenseCategory;
use App\Domain\ValueObject\ExpenseCategoryId;
use App\Infrastructure\Projection\ProjectionManager;

final class PostgresExpenseCategoryRepository implements ExpenseCategoryRepositoryInterface
{
    private EventStoreInterface $eventStore;
    private ProjectionManager $projectionManager; // Assuming changes might be projected

    public function __construct(EventStoreInterface $eventStore, ProjectionManager $projectionManager)
    {
        $this->eventStore = $eventStore;
        $this->projectionManager = $projectionManager;
    }

    public function save(ExpenseCategory $expenseCategory): void
    {
        $domainEvents = $expenseCategory->pullDomainEvents();
        if (!empty($domainEvents)) {
            $this->eventStore->append(...$domainEvents);
            // Dispatch if ExpenseCategory events are projected
            $this->projectionManager->dispatch(...$domainEvents);
        }
    }

    public function findById(ExpenseCategoryId $expenseCategoryId): ?ExpenseCategory
    {
        $events = $this->eventStore->getEventsForAggregate($expenseCategoryId->toString());

        if (empty($events)) {
            return null;
        }

        return ExpenseCategory::reconstituteFromHistory(...$events);
    }
}

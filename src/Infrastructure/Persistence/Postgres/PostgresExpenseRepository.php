<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Postgres;

use App\Application\Port\Out\ExpenseRepositoryInterface;
use App\Application\Port\Out\EventStoreInterface;
use App\Domain\Entity\Expense;
use App\Domain\ValueObject\ExpenseId;
use App\Infrastructure\Projection\ProjectionManager; // Assuming repositories also dispatch to projectors

final class PostgresExpenseRepository implements ExpenseRepositoryInterface
{
    private EventStoreInterface $eventStore;
    private ProjectionManager $projectionManager; // If expenses also have read models updated by projectors

    public function __construct(EventStoreInterface $eventStore, ProjectionManager $projectionManager)
    {
        $this->eventStore = $eventStore;
        $this->projectionManager = $projectionManager;
    }

    public function save(Expense $expense): void
    {
        $domainEvents = $expense->pullDomainEvents();
        if (!empty($domainEvents)) {
            $this->eventStore->append(...$domainEvents);
            // Assuming Expense events might also feed projectors for read models
            // If not, this dispatch call can be omitted for this specific repository.
            // However, for consistency with other ARs, it's included.
            $this->projectionManager->dispatch(...$domainEvents);
        }
    }

    public function findById(ExpenseId $expenseId): ?Expense
    {
        $events = $this->eventStore->getEventsForAggregate($expenseId->toString());

        if (empty($events)) {
            return null;
        }

        return Expense::reconstituteFromHistory(...$events);
    }
}

<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\InMemory;

use App\Application\Port\Out\AccountRepositoryInterface;
use App\Application\Port\Out\EventStoreInterface;
use App\Domain\Entity\Account;
use App\Domain\ValueObject\AccountId;
// We might need Account::reconstituteFromEvents if we were doing full ES load
// use App\Domain\Event\DomainEventInterface;

final class InMemoryAccountRepository implements AccountRepositoryInterface
{
    private EventStoreInterface $eventStore;
    /** @var array<string, Account> */
    private array $accounts = []; // Simulates a read model or cache

    public function __construct(EventStoreInterface $eventStore)
    {
        $this->eventStore = $eventStore;
    }

    public function findById(AccountId $accountId): ?Account
    {
        // Simple lookup for this in-memory version
        return $this->accounts[$accountId->toString()] ?? null;

        // For a stricter ES approach (more complex, requires Account::reconstituteFromEvents):
        // $events = $this->eventStore->getEventsForAggregate($accountId->toString());
        // if (empty($events)) {
        //     return null;
        // }
        // return Account::reconstituteFromEvents($events); // Assuming Account has this static method
    }

    public function save(Account $account): void
    {
        $domainEvents = $account->pullDomainEvents();
        if (!empty($domainEvents)) {
            $this->eventStore->append(...$domainEvents);
        }

        // Store the account instance directly for quick lookups in this in-memory repo
        $this->accounts[$account->getId()->toString()] = $account;
    }

    // Helper method for testing or debugging
    public function getAccountByIdString(string $accountId): ?Account
    {
        return $this->accounts[$accountId] ?? null;
    }

    public function clear(): void // Helper for testing
    {
        $this->accounts = [];
        // Note: This does not clear the event store, which might be desired depending on test setup.
        // If the event store should also be cleared, it needs its own clear method or do it separately.
        // For now, we assume event store is managed (and cleared) independently if needed.
    }
}

<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\InMemory;

use App\Application\Port\Out\TransactionRepositoryInterface;
use App\Domain\Entity\Transaction;
use App\Domain\ValueObject\AccountId;
use App\Domain\ValueObject\TransactionId;

final class InMemoryTransactionRepository implements TransactionRepositoryInterface
{
    /** @var array<string, Transaction> */
    private array $transactions = [];

    public function findById(TransactionId $transactionId): ?Transaction
    {
        return $this->transactions[$transactionId->toString()] ?? null;
    }

    /**
     * @param AccountId $accountId
     * @return Transaction[]
     */
    public function findByAccountId(AccountId $accountId): array
    {
        $foundTransactions = [];
        foreach ($this->transactions as $transaction) {
            if ($transaction->getAccountId()->equals($accountId)) {
                $foundTransactions[] = $transaction;
            }
        }
        // Sort by date, newest first, assuming transactions have a getCreatedAt
        usort($foundTransactions, function (Transaction $a, Transaction $b) {
            return $b->getCreatedAt() <=> $a->getCreatedAt();
        });
        return $foundTransactions;
    }

    public function save(Transaction $transaction): void
    {
        $this->transactions[$transaction->getId()->toString()] = $transaction;
    }

    // Helper method for testing or debugging
    public function getAllTransactions(): array
    {
        return $this->transactions;
    }

    public function clear(): void // Helper for testing
    {
        $this->transactions = [];
    }
}

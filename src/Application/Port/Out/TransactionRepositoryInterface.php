<?php
declare(strict_types=1);
namespace App\Application\Port\Out;
use App\Domain\Entity\Transaction;
use App\Domain\ValueObject\AccountId;
use App\Domain\ValueObject\TransactionId;
/** @return Transaction[] */
interface TransactionRepositoryInterface {
    public function findById(TransactionId $transactionId): ?Transaction;
    public function findByAccountId(AccountId $accountId): array;
    public function save(Transaction $transaction): void;
}

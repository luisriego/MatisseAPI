<?php
declare(strict_types=1);
namespace App\Application\Port\Out;
use App\Domain\Entity\Account;
use App\Domain\ValueObject\AccountId;
interface AccountRepositoryInterface {
    public function findById(AccountId $accountId): ?Account;
    public function save(Account $account): void;
}

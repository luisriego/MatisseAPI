<?php

declare(strict_types=1);

namespace App\Application\Query;

use App\Application\DTO\AccountTransactionsDTO;
use App\Application\DTO\TransactionDTO;
use App\Application\Port\Out\TransactionRepositoryInterface;
use App\Domain\Entity\Transaction;
use DateTimeInterface;

final class GetAccountTransactionsQueryHandler
{
    private TransactionRepositoryInterface $transactionRepository;

    public function __construct(TransactionRepositoryInterface $transactionRepository)
    {
        $this->transactionRepository = $transactionRepository;
    }

    public function handle(GetAccountTransactionsQuery $query): AccountTransactionsDTO
    {
        $transactions = $this->transactionRepository->findByAccountId($query->getAccountId());

        $transactionDTOs = array_map(function (Transaction $transaction) {
            return new TransactionDTO(
                $transaction->getId()->toString(),
                $transaction->getAccountId()->toString(),
                $transaction->getAmount()->getAmount(),
                $transaction->getAmount()->getCurrency()->getCode(),
                $transaction->getType()->value, // PHP 8.1 Enum
                $transaction->getCreatedAt()->format(DateTimeInterface::ATOM) // Formatting date, ISO8601
            );
        }, $transactions);

        return new AccountTransactionsDTO(
            $query->getAccountId()->toString(),
            $transactionDTOs
        );
    }
}

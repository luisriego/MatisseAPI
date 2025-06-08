<?php

declare(strict_types=1);

namespace App\Application\Command;

use App\Application\Port\Out\UnitLedgerAccountRepositoryInterface;
use App\Application\Port\Out\UnitRepositoryInterface; // To validate Unit existence
use App\Domain\ValueObject\Currency;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\TransactionId; // For the payment ID
use DomainException;

final class ReceivePaymentFromUnitCommandHandler
{
    private UnitLedgerAccountRepositoryInterface $unitLedgerAccountRepository;
    private UnitRepositoryInterface $unitRepository;

    public function __construct(
        UnitLedgerAccountRepositoryInterface $unitLedgerAccountRepository,
        UnitRepositoryInterface $unitRepository
    ) {
        $this->unitLedgerAccountRepository = $unitLedgerAccountRepository;
        $this->unitRepository = $unitRepository;
    }

    public function handle(ReceivePaymentFromUnitCommand $command): void
    {
        // Validate Unit existence
        $unit = $this->unitRepository->findById($command->unitId);
        if ($unit === null) {
            throw new DomainException("Unit with ID {$command->unitId->toString()} not found, cannot record payment.");
        }

        $unitLedgerAccount = $this->unitLedgerAccountRepository->findByUnitId($command->unitId);
        if ($unitLedgerAccount === null) {
            // This case might be an error: cannot receive payment for a non-existent ledger account.
            // Or, create one with zero balance and then apply payment (which would make it negative if no fees applied).
            // For now, let's assume it must exist or the fee command should have created it.
            throw new DomainException("UnitLedgerAccount for Unit ID {$command->unitId->toString()} not found.");
        }

        $paymentAmount = new Money($command->amountCents, new Currency($command->currencyCode));
        $paymentId = TransactionId::generate(); // Generate a unique ID for this payment transaction

        try {
            $paymentDate = new \DateTimeImmutable($command->paymentDate);
        } catch (\Exception $e) {
            throw new \InvalidArgumentException("Invalid payment date format: {$command->paymentDate}. Please use YYYY-MM-DD.");
        }

        // The receivePayment method in UnitLedgerAccount records the event
        $unitLedgerAccount->receivePayment($paymentAmount, $paymentId, $paymentDate, $command->paymentMethod);

        $this->unitLedgerAccountRepository->save($unitLedgerAccount);
    }
}

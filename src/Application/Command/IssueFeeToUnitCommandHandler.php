<?php

declare(strict_types=1);

namespace App\Application\Command;

use App\Application\Port\Out\FeeItemRepositoryInterface;
use App\Application\Port\Out\UnitLedgerAccountRepositoryInterface;
use App\Application\Port\Out\UnitRepositoryInterface;
use App\Domain\Entity\UnitLedgerAccount;
use App\Domain\ValueObject\Currency;
use App\Domain\ValueObject\Money;
use DomainException;

final class IssueFeeToUnitCommandHandler
{
    private UnitLedgerAccountRepositoryInterface $unitLedgerAccountRepository;
    private UnitRepositoryInterface $unitRepository; // To validate Unit existence
    private FeeItemRepositoryInterface $feeItemRepository; // To validate FeeItem existence

    public function __construct(
        UnitLedgerAccountRepositoryInterface $unitLedgerAccountRepository,
        UnitRepositoryInterface $unitRepository,
        FeeItemRepositoryInterface $feeItemRepository
    ) {
        $this->unitLedgerAccountRepository = $unitLedgerAccountRepository;
        $this->unitRepository = $unitRepository;
        $this->feeItemRepository = $feeItemRepository;
    }

    public function handle(IssueFeeToUnitCommand $command): void
    {
        // Validate Unit existence
        $unit = $this->unitRepository->findById($command->unitId);
        if ($unit === null) {
            throw new DomainException("Unit with ID {$command->unitId->toString()} not found.");
        }

        // Validate FeeItem existence
        $feeItem = $this->feeItemRepository->findById($command->feeItemId);
        if ($feeItem === null) {
            throw new DomainException("FeeItem with ID {$command->feeItemId->toString()} not found.");
        }
        // Optionally, check if FeeItem's condominiumId matches the Unit's condominiumId
        if (!$feeItem->getCondominiumId()->equals($unit->getCondominiumId())) {
            throw new DomainException("FeeItem does not belong to the unit's condominium.");
        }

        $unitLedgerAccount = $this->unitLedgerAccountRepository->findByUnitId($command->unitId);

        if ($unitLedgerAccount === null) {
            // Create a new UnitLedgerAccount with zero balance if it doesn't exist
            // Assuming default currency for new account can be derived or is standard (e.g. FeeItem's currency)
            $initialBalance = new Money(0, new Currency($command->currencyCode));
            $unitLedgerAccount = UnitLedgerAccount::createNew($command->unitId, $initialBalance);
            // First save is important to persist the creation event
            $this->unitLedgerAccountRepository->save($unitLedgerAccount);
        }

        $feeAmount = new Money($command->amountCents, new Currency($command->currencyCode));

        try {
            $dueDate = new \DateTimeImmutable($command->dueDate);
        } catch (\Exception $e) {
            throw new \InvalidArgumentException("Invalid due date format: {$command->dueDate}. Please use YYYY-MM-DD.");
        }

        // The applyFee method in UnitLedgerAccount records the event
        $unitLedgerAccount->applyFee($command->feeItemId, $feeAmount, $dueDate, $command->description);

        $this->unitLedgerAccountRepository->save($unitLedgerAccount);
    }
}

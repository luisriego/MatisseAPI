<?php

declare(strict_types=1);

namespace App\Infrastructure\EventSourcing;

// Import all domain event classes
use App\Domain\Event\AccountCreated;
use App\Domain\Event\MoneyDeposited;
use App\Domain\Event\MoneyWithdrawn;
use App\Domain\Event\WithdrawalFailedDueToInsufficientFunds;

use App\Domain\Event\CondominiumRegisteredEvent;
use App\Domain\Event\CondominiumRenamedEvent;
use App\Domain\Event\CondominiumAddressChangedEvent;
use App\Domain\Event\ExpenseCategoryCreatedEvent;
use App\Domain\Event\ExpenseRecordedEvent;
use App\Domain\Event\FeeItemCreatedEvent;
use App\Domain\Event\FeeAppliedToUnitLedgerEvent;
use App\Domain\Event\LedgerCreatedEvent;
use App\Domain\Event\LedgerCreditedEvent;
use App\Domain\Event\LedgerDebitedEvent;
use App\Domain\Event\OwnerAssignedToUnitEvent;
use App\Domain\Event\OwnerCreatedEvent;
use App\Domain\Event\OwnerContactInfoUpdatedEvent;
use App\Domain\Event\OwnerRemovedFromUnitEvent;
use App\Domain\Event\PaymentReceivedOnUnitLedgerEvent;
use App\Domain\Event\UnitCreatedEvent;
use App\Domain\Event\UnitLedgerAccountCreatedEvent;


class EventMap
{
    /**
     * @return array<string, class-string<\App\Domain\Event\DomainEventInterface>>
     */
    public static function getMap(): array
    {
        return [
            // Old Account events (if still needed for any reason, though being phased out)
            AccountCreated::eventType() => AccountCreated::class,
            MoneyDeposited::eventType() => MoneyDeposited::class,
            MoneyWithdrawn::eventType() => MoneyWithdrawn::class,
            WithdrawalFailedDueToInsufficientFunds::eventType() => WithdrawalFailedDueToInsufficientFunds::class,

            // Condominium Events
            CondominiumRegisteredEvent::eventType() => CondominiumRegisteredEvent::class,
            CondominiumRenamedEvent::eventType() => CondominiumRenamedEvent::class,
            CondominiumAddressChangedEvent::eventType() => CondominiumAddressChangedEvent::class,

            // Owner Events
            OwnerCreatedEvent::eventType() => OwnerCreatedEvent::class,
            OwnerContactInfoUpdatedEvent::eventType() => OwnerContactInfoUpdatedEvent::class,

            // Unit Events
            UnitCreatedEvent::eventType() => UnitCreatedEvent::class,
            OwnerAssignedToUnitEvent::eventType() => OwnerAssignedToUnitEvent::class,
            OwnerRemovedFromUnitEvent::eventType() => OwnerRemovedFromUnitEvent::class,

            // Ledger Events
            LedgerCreatedEvent::eventType() => LedgerCreatedEvent::class,
            LedgerCreditedEvent::eventType() => LedgerCreditedEvent::class,
            LedgerDebitedEvent::eventType() => LedgerDebitedEvent::class,

            // UnitLedgerAccount Events
            UnitLedgerAccountCreatedEvent::eventType() => UnitLedgerAccountCreatedEvent::class,
            FeeAppliedToUnitLedgerEvent::eventType() => FeeAppliedToUnitLedgerEvent::class,
            PaymentReceivedOnUnitLedgerEvent::eventType() => PaymentReceivedOnUnitLedgerEvent::class,

            // Configuration Item Events (FeeItem, ExpenseCategory)
            FeeItemCreatedEvent::eventType() => FeeItemCreatedEvent::class,
            ExpenseCategoryCreatedEvent::eventType() => ExpenseCategoryCreatedEvent::class,

            // Expense Event
            ExpenseRecordedEvent::eventType() => ExpenseRecordedEvent::class,

            // Add any other events here as they are created
        ];
    }
}
